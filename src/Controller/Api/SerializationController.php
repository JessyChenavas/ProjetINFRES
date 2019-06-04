<?php

namespace App\Controller\Api;

class SerializationController
{
    public function serialize(string $entity, bool $paginated = false) {

        $serialization = array();

        switch ($entity) {

            case 'user':
                $serialization = ['id', 'username', 'email', 'roles', 'nom', 'prenom', 'genre', 'dateNaissance', 'promotion',
                    'voiture' => ['modele', 'marque', 'couleur']];
                break;

            case 'conversation':
                $serialization = ['id', 'messages' => ['texte', 'date', 'auteur' => ['username']], 'participants' => ['id', 'username', 'email', 'roles', 'nom', 'prenom', 'genre', 'dateNaissance', 'promotion',
                    'voiture' => ['modele', 'marque', 'couleur']]];
                break;

            case 'trajet':
                $serialization = [ 'id', 'lieuDepart', 'lieuArrive', 'heureDepart', 'passagersMax', 'tarif', 'createur' => ['id', 'username', 'email', 'nom', 'prenom', 'genre', 'dateNaissance', 'promotion',
                    'voiture' => ['modele', 'marque', 'couleur']],  'passagers' => ['id','username','email']];
                break;

            case 'evenement':
                $serialization = [ 'id', 'titre', 'description', 'lieu', 'date', 'image', 'auteur' => ['id','username','email']];
                break;

            case 'annonce':
                $serialization = [ 'id', 'titre', 'description', 'prix', 'images', 'createur' => ['id', 'username', 'email', 'nom', 'prenom', 'promotion']];
                break;
        }

        if ($paginated) {
            $serialization = [ 'attributes' => [ 'meta', 'data' => $serialization]];
        } else {
            $serialization = [ 'attributes' => $serialization ];
        }

        return $serialization;
    }
}