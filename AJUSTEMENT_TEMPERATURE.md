# Ajustement des capteurs - Documentation

## Regles d'ajustement

La fonction `calculerAjustement()` applique un ajustement numerique en fonction de la valeur d'un capteur. Les memes regles s'appliquent a **tous les capteurs** :

| Condition | Ajustement |
|---|---|
| Valeur < 10 (paire ou impaire) | **+40** |
| Valeur >= 10 et **paire** (ex: 20, 22, 24) | **+130** |
| Valeur >= 10 et **impaire** (ex: 21, 23, 25) | **+150** |

**Note :** La parite est determinee sur la partie entiere de la valeur (`intval()`). Par exemple, 25.3 est consideree comme impaire (25), donc ajustement = +150.

## Capteurs concernes

| Capteur | Variable | Exemple de valeur | Ajustement |
|---|---|---|---|
| Temperature | `$temperature` | 26.0 °C (pair) | +130 |
| Humidite | `$humidity` | 55.3 % (impair) | +150 |
| Niveau de lumiere | `$lumiere` | 700 lux (pair) | +130 |
| Niveau d'eau | `$niveau_eau` | 68.2 % (pair) | +130 |
| Arrosage | `$arrosage` | 0 ou 1 (< 10) | +40 |
| CO2 | `$co2` | 420 ppm (pair) | +130 |

**Cas particulier - Arrosage :** La valeur est toujours 0 (INACTIF) ou 1 (ACTIF), donc toujours < 10, ce qui donne systematiquement un ajustement de **+40**.

## Exemples

| Valeur | Partie entiere | Parite | Ajustement |
|---|---|---|---|
| 5.2 | 5 | - (< 10) | +40 |
| 8.0 | 8 | - (< 10) | +40 |
| 0 | 0 | - (< 10) | +40 |
| 1 | 1 | - (< 10) | +40 |
| 20.5 | 20 | paire | +130 |
| 25.0 | 25 | impaire | +150 |
| 55.3 | 55 | impaire | +150 |
| 700 | 700 | paire | +130 |
| 420 | 420 | paire | +130 |

## Fichier modifie

### `auto.php` (racine du projet)

C'est le simulateur IoT qui genere les donnees de capteurs toutes les 5 secondes.

**Ce qui a ete ajoute / modifie :**

1. **Fonction `calculerAjustement($valeur)`** (lignes 26-35) :
   - Fonction generique qui recoit n'importe quelle valeur capteur
   - Convertit en entier avec `intval()`
   - Applique les regles : < 10 => +40, paire => +130, impaire => +150
   - Retourne la valeur d'ajustement

2. **Calcul des ajustements** (lignes 50 et 81-86) :
   - `$ajust_temperature` : ajustement pour la temperature
   - `$ajust_humidite` : ajustement pour l'humidite
   - `$ajust_lumiere` : ajustement pour le niveau de lumiere
   - `$ajust_eau` : ajustement pour le niveau d'eau
   - `$ajust_arrosage` : ajustement pour l'arrosage
   - `$ajust_co2` : ajustement pour le CO2

3. **Affichage** (tableau HTML) :
   - Un tableau recapitulatif affiche pour chaque capteur : la valeur, la base entiere, et l'ajustement calcule

## Structure du code

```php
function calculerAjustement($valeur) {
    $entier = intval($valeur);
    if ($entier < 10) {
        return 40;
    }
    if ($entier % 2 == 0) {
        return 130;
    }
    return 150;
}

// Utilisation pour chaque capteur :
$ajust_temperature = calculerAjustement($temperature);
$ajust_humidite    = calculerAjustement($humidity);
$ajust_lumiere     = calculerAjustement($lumiere);
$ajust_eau         = calculerAjustement($niveau_eau);
$ajust_arrosage    = calculerAjustement($arrosage);
$ajust_co2         = calculerAjustement($co2);
```

## Emplacement dans le projet

```
Project_final/
    auto.php                      <-- Fichier modifie (simulateur IoT)
    AJUSTEMENT_TEMPERATURE.md     <-- Ce fichier de documentation
```
