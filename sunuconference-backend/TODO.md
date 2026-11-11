# TODO

## Reviewer/submission assignment

`reviewer_topic` représente une compétence thématique du reviewer pour un topic donné. Ce n'est PAS une affectation de relecture réelle sur une soumission précise.

Une future table `reviewer_submission` sera nécessaire pour gérer l'assignation fine reviewer ↔ soumission. Cette table devra aussi déclencher proprement la transition de statut `submitted` -> `under_review` lorsqu'une soumission reçoit sa première affectation réelle de reviewer.