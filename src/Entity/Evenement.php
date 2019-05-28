<?php
namespace App\Entity;

use Doctrine\ORM\Mapping as ORM;
use Symfony\Component\Validator\Constraints as Assert;
use App\Entity\User;

/**
 * @ORM\Entity
 * @ORM\Table(name="evenement")
 */
class Evenement {
    /**
     * @ORM\Column(type="integer")
     * @ORM\Id
     * @ORM\GeneratedValue(strategy="AUTO")
     */
    private $id;
    /**
     * @ORM\Column(type="string", length=100)
     * @Assert\NotBlank()
     *
     */
    private $titre;
    /**
     * @ORM\Column(type="text")
     * @Assert\NotBlank()
     */
    private $description;
    /**
     * @return mixed
     */

    /**
     * @ORM\Column(type="string", length=100)
     * @Assert\NotBlank()
     */
    private $lieu;

    /**
     * @ORM\Column(type="datetime")
     * @Assert\DateTime()
     * @Assert\GreaterThan(
     *     "+30 minutes",
     *     message = "Merci de renseigner une date valable (au moins 30 minutes après la date actuelle)")
     */
    private $date;

    /**
     * @ORM\ManyToOne(targetEntity="User", cascade={"persist"}, fetch="EAGER")
     * @ORM\JoinColumn(nullable=false)
     * @Assert\Valid()
     */
    private $auteur;

    /**
     * @param $date
     */
    public function __construct()
    {
        $this->date = new \DateTime();
    }

    /**
     * @return mixed
     */
    public function getDate()
    {
        return $this->date;
    }

    /**
     * @param mixed $date
     */
    public function setDate($date)
    {
        $this->date = $date;
    }

    /**
     * @return mixed
     */
    public function getLieu()
    {
        return $this->lieu;
    }

    /**
     * @param mixed $lieu
     */
    public function setLieu($lieu)
    {
        $this->lieu = $lieu;
    }

    public function getId()
    {
        return $this->id;
    }
    /**
     * @param mixed $id
     */
    public function setId($id)
    {
        $this->id = $id;
    }
    /**
     * @return mixed
     */
    public function getTitre()
    {
        return $this->titre;
    }
    /**
     * @param mixed $titre
     */
    public function setTitre($titre)
    {
        $this->titre = $titre;
    }
    /**
     * @return mixed
     */
    public function getDescription()
    {
        return $this->description;
    }
    /**
     * @param mixed $description
     */
    public function setDescription($description)
    {
        $this->description = $description;
    }

    /**
     * @return mixed
     */
    public function getAuteur()
    {
        return $this->auteur;
    }

    /**
     * @param mixed $author
     */
    public function setAuteur(User $auteur)
    {
        $this->auteur = $auteur;
    }

    /**
     * @return bool
     */
    public function estAuteur(User $user = null) {
        return $user && $user->getId() === $this->getAuteur()->getId();
    }
}