<?php

namespace App\Entity;

use FOS\UserBundle\Model\User as BaseUser;
use Doctrine\ORM\Mapping as ORM;
use Symfony\Component\Validator\Constraints as Assert;
use App\Entity\Voiture;

/**
 * @ORM\Entity
 * @ORM\Table(name="fos_user")
 */
class User extends BaseUser
{
    /**
     * @ORM\Id
     * @ORM\Column(type="integer")
     * @ORM\GeneratedValue(strategy="AUTO")
     */
    protected $id;

    /**
     * @ORM\Column(type="string", length=40)
     * @Assert\NotBlank()
     */
    private $nom;

    /**
     * @ORM\Column(type="string", length=40)
     * @Assert\NotBlank()
     */
    private $prenom;

    /**
     * @ORM\Column(type="string", length=20)
     * @Assert\NotBlank()
     */
    private $genre;

    /**
     * @ORM\Column(type="date")
     * @Assert\Date()
     * @Assert\GreaterThanOrEqual("1900-01-01")
     */
    private $dateNaissance;

    /**
     * @ORM\Column(type="string", length=10)
     * @Assert\NotBlank()
     * @Assert\Regex(
     *     pattern="/(CMC|MKX|FI|INFRES)/",
     *     message="Promotion invalide ! (Choisir selon CMC, MKX, FI ou INFRES)"
     * )
     */
    private $promotion;

    /**
     * @ORM\ManyToOne(targetEntity="Voiture", cascade={"persist"}, fetch="EAGER")
     * @ORM\JoinColumn(nullable=true)
     * @Assert\Valid()
     */
    private $voiture;

    public function __construct()
    {
        parent::__construct();
    }
    /**
     * @return mixed
     */
    public function getId()
    {
        return $this->id;
    }

    /**
     * @return mixed
     */
    public function getPromotion()
    {
        return $this->promotion;
    }

    /**
     * @param mixed $promotion
     */
    public function setPromotion($promotion)
    {
        $this->promotion = $promotion;

        return $this;
    }

    /**
     * @return mixed
     */
    public function getVoiture()
    {
        return $this->voiture;
    }

    /**
     * @param mixed $voiture
     */
    public function setVoiture(Voiture $voiture)
    {
        $this->voiture = $voiture;

        return $this;
    }

    /**
     * @return mixed
     */
    public function getDateNaissance()
    {
        return $this->dateNaissance;
    }

    /**
     * @param mixed $dateNaissance
     */
    public function setDateNaissance($dateNaissance)
    {
        $this->dateNaissance = $dateNaissance;

        return $this;
    }

    /**
     * @return mixed
     */
    public function getGenre()
    {
        return $this->genre;
    }

    /**
     * @param mixed $genre
     */
    public function setGenre($genre)
    {
        $this->genre = $genre;

        return $this;
    }

    /**
     * @return mixed
     */
    public function getNom()
    {
        return $this->nom;
    }

    /**
     * @param mixed $nom
     */
    public function setNom($nom)
    {
        $this->nom = $nom;

        return $this;
    }

    /**
     * @return mixed
     */
    public function getPrenom()
    {
        return $this->prenom;
    }

    /**
     * @param mixed $prenom
     */
    public function setPrenom($prenom)
    {
        $this->prenom = $prenom;

        return $this;
    }

    public function updateRole($role) {
        $this->roles = array();
        $this->addRole($role);
    }
}