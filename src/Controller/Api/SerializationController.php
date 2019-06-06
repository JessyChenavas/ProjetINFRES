<?php

namespace App\Controller\Api;

class SerializationController
{
    private $user_resume =  ['id', 'username', 'email', 'roles', 'nom', 'prenom', 'genre', 'dateNaissance', 'promotion'];

    private $user_detail = ['id', 'username', 'email', 'roles', 'nom', 'prenom', 'genre', 'dateNaissance', 'promotion',
    'voiture' => ['modele', 'marque', 'couleur']];


    public function serialize(string $entity, bool $paginated = false) {

        $serialization = array();

        switch ($entity) {

            case 'user':
                $serialization = $this->user_detail;
                break;

            case 'conversation':
                $serialization = ['id', 'messages' => ['texte', 'date', 'auteur' => ['username']], 'participants' => $this->user_resume];
                break;

            case 'trajet':
                $serialization = [ 'id', 'lieuDepart', 'lieuArrive', 'heureDepart', 'passagersMax', 'tarif', 'createur' => $this->user_detail,  'passagers' => $this->user_resume ];
                break;

            case 'evenement':
                $serialization = [ 'id', 'titre', 'description', 'lieu', 'date', 'image', 'limiteParticipants' ,'auteur' => $this->user_resume, 'participants' => $this->user_resume ];
                break;

            case 'annonce':
                $serialization = [ 'id', 'titre', 'description', 'prix', 'images', 'createur' => $this->user_resume ];
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