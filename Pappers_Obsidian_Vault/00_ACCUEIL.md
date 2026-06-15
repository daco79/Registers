# Vault Obsidian — Pappers MCP

Date de constitution : 2026-06-14

## Objectif

Documenter la structure exploitable via le MCP Pappers, avec un focus sur :

- [[03_Entreprises/ENTREPRISES - Vue d'ensemble|Entreprises]]
- [[04_Immobilier/IMMOBILIER - Vue d'ensemble|Immobilier]]
- [[05_Modele_relationnel/Graphe entites-relations|Graphe entités-relations]]
- [[06_Playbooks_requetes/Strategie economie credits|Stratégie économie crédits]]

## Méthode utilisée

J’ai d’abord exploité la découverte des outils et des schémas disponibles, ce qui donne déjà énormément d’information sans multiplier les requêtes métier.

Ensuite, seulement deux appels métier ont été faits pour observer la forme réelle des JSON :

1. `informations-entreprise` sur un SIREN d’exemple.
2. `recherche-parcelles` sur une adresse d’exemple.

## Limite importante

Ce vault décrit la structure **observée et inférée depuis le MCP/API exposé**, pas la structure interne exacte des bases Pappers.  
Les champs peuvent être absents, `null`, variables selon les sources, ou soumis à habilitation.

## Lecture recommandée

Commencer par :

1. [[01_Cartographie_globale]]
2. [[03_Entreprises/Schema - Informations entreprise]]
3. [[04_Immobilier/Schema - Parcelles]]
4. [[05_Modele_relationnel/Tables candidates - Entreprises]]
5. [[05_Modele_relationnel/Tables candidates - Immobilier]]
