# Ajustement de temperature - Documentation

## Regles d'ajustement

La fonction `calculerAjustement()` applique un ajustement numerique en fonction de la temperature mesuree. Voici les regles :

| Condition | Ajustement |
|---|---|
| Temperature < 10 (paire ou impaire) | **+40** |
| Temperature >= 10 et **paire** (ex: 20, 22, 24) | **+130** |
| Temperature >= 10 et **impaire** (ex: 21, 23, 25) | **+150** |

**Note :** La parite est determinee sur la partie entiere de la temperature (`intval()`). Par exemple, une temperature de 25.3 est consideree comme impaire (25), donc ajustement = +150.

## Exemples

| Temperature | Partie entiere | Parite | Ajustement |
|---|---|---|---|
| 5.2 | 5 | - (< 10) | +40 |
| 8.0 | 8 | - (< 10) | +40 |
| 20.5 | 20 | paire | +130 |
| 25.0 | 25 | impaire | +150 |
| 32.1 | 32 | paire | +130 |
| 21.7 | 21 | impaire | +150 |

## Fichier modifie

### `auto.php` (racine du projet)

C'est le simulateur IoT qui genere les donnees de capteurs toutes les 5 secondes.

**Ce qui a ete ajoute :**

1. **Fonction `calculerAjustement($temperature)`** (lignes 26-35) :
   - Recoit la temperature en parametre
   - Convertit en entier avec `intval()`
   - Applique les regles : < 10 => +40, paire => +130, impaire => +150
   - Retourne la valeur d'ajustement

2. **Calcul de l'ajustement** (ligne 50) :
   - Appel de la fonction juste apres la generation de la temperature
   - Le resultat est stocke dans `$ajustement`

3. **Affichage** (ligne 110) :
   - Ajout d'une ligne dans la sortie HTML : "Ajustement temperature : +X (base entiere : Y)"
   - Permet de verifier visuellement le calcul

## Structure du code ajoute

```php
function calculerAjustement($temperature) {
    $tempEntiere = intval($temperature);
    if ($tempEntiere < 10) {
        return 40;
    }
    if ($tempEntiere % 2 == 0) {
        return 130;
    }
    return 150;
}

// Utilisation :
$ajustement = calculerAjustement($temperature);
```

## Emplacement dans le projet

```
Project_final/
    auto.php                      <-- Fichier modifie (simulateur IoT)
    AJUSTEMENT_TEMPERATURE.md     <-- Ce fichier de documentation
```
