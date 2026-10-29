# Ordre de Traitement des Incidents (INTERVENTION.md)

| Rang | Incident | Gravit‚ | Urgence | Justification |
| :--- | :--- | :--- | :--- | :--- |
| **1** | **INC-206** | Critique | Imm‚diate | Panne totale caus‚e par le blocage synchrone du service d'identit‚, paralysant tout le systŠme. |
| **2** | **INC-201** | Haute | Haute | Doublons physiques de num‚ros de table entraŒnant des collisions de candidats en salle d'examen. |
| **3** | **INC-202** | Haute | Haute | D‚passement physique de capacit‚ (ex. Lokossa : 307 candidats pour 300 places assises disponibles). |
| **4** | **INC-204** | Haute | Moyenne | ProblŠme de fuseau horaire UTC/B‚nin privant des candidats l‚gitimes de leur droit d'inscription en fin de campagne. |
| **5** | **INC-205** | Moyenne | Moyenne | Biais dans le calcul math‚matique de l'ƒge ‚liminant … tort des candidats ‚ligibles. |
| **6** | **INC-203** | Moyenne | Basse | Double soumission de formulaire due … l'absence de cl‚ d'idempotence ou de verrouillage client/serveur. |
| **7** | **INC-207** | Basse | Moyenne | D‚gradation des performances de consultation (`GET /inscrits`) due … l'absence d'indexation SQL et de pagination. |
| **8** | **INC-208** | Basse | Basse | Blocage fonctionnel li‚ … une interpr‚tation erron‚e ou ambigu‰ des rŠgles de chevauchement. |
