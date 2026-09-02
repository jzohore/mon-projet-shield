<?php

declare(strict_types=1);

namespace App\Domain\Workspace\Enum;

enum OriasCheckOutcome: string
{
    /** L'intermédiaire est inscrit et actif sur le registre ORIAS. */
    case VALID = 'valid';

    /** Réponse définitive : le numéro ne correspond à aucun intermédiaire inscrit. */
    case NOT_REGISTERED = 'not_registered';

    /** Le registre ORIAS n'a pas pu être interrogé (réseau, 5xx, page illisible). À réessayer. */
    case UNAVAILABLE = 'unavailable';

    /**
     * Vrai si le résultat est définitif : on peut mettre à jour le profil et
     * ne pas replanifier de vérification.
     */
    public function isConclusive(): bool
    {
        return self::UNAVAILABLE !== $this;
    }
}
