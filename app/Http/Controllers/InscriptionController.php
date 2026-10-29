<?php

namespace App\Http\Controllers;

use App\Models\Centre;
use App\Models\Inscription;
use Illuminate\Http\Request;
use Illuminate\Support\Facades\DB;
use Illuminate\Support\Facades\Http;
use Carbon\Carbon;

class InscriptionController extends Controller
{
    public function store(Request $request)
    {
        // 1. Validation des données entrantes
        $request->validate([
            'concours_id' => 'required|integer',
            'centre_id' => 'required|exists:centres,id',
            'npi' => 'required|string|max:10',
            'date_naissance' => 'required|date',
        ]);

        return DB::transaction(function () use ($request) {
            // 2. Verrouillage de la ligne du centre pour éviter la concurrence (INC-201 / INC-202)
            $centre = Centre::lockForUpdate()->findOrFail($request->centre_id);

            // 3. Vérification stricte de la capacité du centre
            $totalInscritsCentre = Inscription::where('centre_id', $centre->id)->count();
            if ($totalInscritsCentre >= $centre->capacite) {
                return response()->json([
                    'erreur' => 'Capacité maximale atteinte pour ce centre.'
                ], 400);
            }

            // 4. Vérification de l'âge limite (Ex: 35 ans max au 31 décembre de l'année en cours)
            $dateLimiteAge = Carbon::create(now()->year, 12, 31);
            $age = Carbon::parse($request->date_naissance)->diffInYears($dateLimiteAge);
            if ($age > 35) {
                return response()->json([
                    'erreur' => 'Âge limite dépassé pour ce concours.'
                ], 400);
            }

            // 5. Appel sécurisé au service externe d'identité avec un Timeout de 5s (INC-206)
            $statut = 'valide';
            try {
                $response = Http::timeout(5)->get("http://service-identification/identite/{$request->npi}");
                if ($response->failed()) {
                    $statut = 'en_attente_verification';
                }
            } catch (\Exception $e) {
                // En cas de lenteur ou de panne du service externe, on bascule en attente au lieu de bloquer
                $statut = 'en_attente_verification';
            }

            // 6. Attribution sécurisée et séquentielle du numéro de table (Ex: L-0001)
            $dernierNumero = Inscription::where('centre_id', $centre->id)
                ->orderBy('id', 'desc')
                ->value('numero_table');

            $prochainSeq = $dernierNumero ? intval(substr($dernierNumero, 2)) + 1 : 1;
            $numeroTable = $centre->code . '-' . str_pad($prochainSeq, 4, '0', STR_PAD_LEFT);

            // 7. Enregistrement final protégé par la contrainte d'unicité de la base de données
            try {
                $inscription = Inscription::create([
                    'concours_id' => $request->concours_id,
                    'centre_id' => $centre->id,
                    'npi' => $request->npi,
                    'numero_table' => $numeroTable,
                    'statut' => $statut,
                    'cree_le' => now()->utc(),
                ]);
            } catch (\Illuminate\Database\QueryException $e) {
                // Intercepte les doubles soumissions simultanées (INC-203)
                return response()->json([
                    'erreur' => 'Une inscription existe déjà pour ce candidat à ce concours.'
                ], 409);
            }

            return response()->json([
                'message' => 'Inscription enregistrée avec succès.',
                'data' => $inscription
            ], 201);
        });
    }
}