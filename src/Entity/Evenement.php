<?php
namespace App\Entity;

use Doctrine\Common\Collections\ArrayCollection;
use Doctrine\ORM\Mapping as ORM;
use Symfony\Component\Validator\Constraints as Assert;

/**
 * @ORM\Entity (repositoryClass="App\Repository\EvenementRepository")
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
     * @Assert\NotBlank(message="Merci de renseigner un titre !")
     */
    private $titre;

    /**
     * @ORM\Column(type="text")
     * @Assert\NotBlank(message="Merci de renseigner une description !")
     */
    private $description;

    /**
     * @ORM\Column(type="string", length=100)
     * @Assert\NotBlank(message="Merci de renseigner un lieu !")
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
     * @ORM\ManyToOne(targetEntity="Image", cascade={"persist"}, fetch="EAGER")
     * @ORM\JoinColumn(nullable=true)
     * @Assert\Valid()
     */
    private $image;

    /**
     * @ORM\ManyToMany(targetEntity="User", cascade={"persist"}, fetch="EAGER")
     * @ORM\JoinColumn(nullable=false)
     * @Assert\Valid()
     */
    private $participants;

    /**
     * @ORM\Column(type="integer", nullable=true)
     */
    private $limiteParticipants;

    /**
     * @param $date
     */
    public function __construct()
    {
        $this->date = new \DateTime();
        $this->participants = new ArrayCollection();
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

    /**
     * @return mixed
     */
    public function getImage()
    {
        return $this->image;
    }

    /**
     * @param mixed $image
     */
    public function setImage(Image $image)
    {
        $this->image = $image;
    }

    /**
     * @return mixed
     */
    public function getParticipants()
    {
        return $this->participants;
    }

    public function addParticipant(User $user)  {
        $ajoutDisponible = !$this->estComplet();

        if ($ajoutDisponible) {
            if ($this->participants->contains($user)) { return 0; }
            $this->participants[] = $user;
        }

        return $ajoutDisponible;
    }

   public function removeParticipant(User $user) {
        $this->participants->removeElement($user);
   }

    /**
     * @return mixed
     */
    public function getLimiteParticipants()
    {
        return $this->limiteParticipants;
    }

    /**
     * @param mixed $limiteParticipants
     */
    public function setLimiteParticipants($limiteParticipants)
    {
        $this->limiteParticipants = $limiteParticipants;
    }

   public function estComplet () {
        if (!$this->limiteParticipants) { return 0; }

        return $this->participants->count() >= $this->limiteParticipants;
   }
}