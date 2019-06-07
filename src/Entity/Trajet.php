<?php


namespace App\Entity;

use Doctrine\ORM\Mapping as ORM;
use Doctrine\Common\Collections\ArrayCollection;
use Symfony\Component\Validator\Constraints as Assert;
use Hateoas\Configuration\Annotation as Hateoas;
use JMS\Serializer\Annotation as Serializer;

/**
 * @ORM\Entity
 * @ORM\Table(name="trajet")
 *
 * @Hateoas\Relation("self",
 *      href = @Hateoas\Route("api_afficher_trajet", parameters = { "id" = "expr(object.getId())" }))
 *
 * @Hateoas\Relation("edit",
 *      href = @Hateoas\Route("api_modifier_trajet", parameters = { "id" = "expr(object.getId())" }))
 *
 * @Hateoas\Relation("delete",
 *      href = @Hateoas\Route("api_supprimer_trajet", parameters = { "id" = "expr(object.getId())" }))
 *
 *  @Hateoas\Relation("createur", embedded = "expr(object.getCreateur())")
 *
 *  @Hateoas\Relation("passagers", embedded = "expr(object.getPassagers())")
 * )
 */
class Trajet
{
    /**
     * @ORM\Column(type="integer")
     * @ORM\Id
     * @ORM\GeneratedValue(strategy="AUTO")
     */
    private $id;

    /**
     * @ORM\ManyToOne(targetEntity="User", cascade={"persist"}, fetch="EAGER")
     * @ORM\JoinColumn(nullable=false)
     * @Assert\Valid()
     *
     * @Serializer\Exclude()
     */
    private $createur;

    /**
     * @ORM\Column(type="string", length=100)
     * @Assert\NotBlank(message="Merci de renseigner un lieu de départ !")
     */
    private $lieuDepart;

    /**
     * @ORM\Column(type="string", length=100)
     * @Assert\NotBlank(message="Merci de renseigner un lieu d'arrivé !")
     */
    private $lieuArrive;

    /**
     * @ORM\Column(type="datetime")
     * @Assert\DateTime()
     * @Assert\GreaterThan(
     *     "+5 minutes",
     *     message = "Merci de renseigner une date valable ! (au moins 5 minutes après la date actuelle)")
     */
    private $heureDepart;

    /**
     * @ORM\Column(type="integer")
     * @Assert\Range(
     *      min = 1,
     *      minMessage = "Merci de renseigner au moins un passager maximal !"
     * )
     */
    private $passagersMax;

    /**
     * @ORM\ManyToMany(targetEntity="User", cascade={"persist"})
     * @Assert\Valid()
     *
     * @Serializer\Exclude()
     */
    private $passagers;

    /**
     * @ORM\Column(type="decimal", precision=7, scale=2)
     * @Assert\Range(
     *     min = 0,
     *     minMessage = "La somme ne peut pas être négative !",
     *     max = 50,
     *     maxMessage = "La somme doit être inférieure à 50€"
     *  )
     */
    private $tarif;

    public function __construct()
    {
        $this->passagers = new ArrayCollection();
        $this->heureDepart = new \DateTime();
    }

    /**
     * @return mixed
     */
    public function getCreateur()
    {
        return $this->createur;
    }

    /**
     * @param mixed $createur
     */
    public function setCreateur(User $createur)
    {
        $this->createur = $createur;

        return $this;
    }

    /**
     * @return mixed
     */
    public function getLieuDepart()
    {
        return $this->lieuDepart;
    }

    /**
     * @param mixed $lieuDepart
     */
    public function setLieuDepart($lieuDepart)
    {
        $this->lieuDepart = $lieuDepart;

        return $this;
    }

    /**
     * @return mixed
     */
    public function getLieuArrive()
    {
        return $this->lieuArrive;
    }

    /**
     * @param mixed $lieuArrive
     */
    public function setLieuArrive($lieuArrive)
    {
        $this->lieuArrive = $lieuArrive;

        return $this;
    }

    /**
     * @return mixed
     */
    public function getHeureDepart()
    {
        return $this->heureDepart;
    }

    /**
     * @param mixed $heureDepart
     */
    public function setHeureDepart($heureDepart)
    {
        $this->heureDepart = $heureDepart;

        return $this;
    }

    /**
     * @return mixed
     */
    public function getPassagersMax()
    {
        return $this->passagersMax;
    }

    /**
     * @param mixed $passagersMax
     */
    public function setPassagersMax($passagersMax)
    {
        $this->passagersMax = $passagersMax;

        return $this;
    }

    /**
     * @return mixed
     */
    public function getPassagers()
    {
        return $this->passagers;
    }

    /**
     * @return mixed
     */
    public function addPassager(User $passager)
    {
        $newPassagerAvailable = !$this->estPlein();
        if ($newPassagerAvailable) {
            if ($this->passagers->contains($passager)) { return 0; }

            $this->passagers[] = $passager;
        }

        return $newPassagerAvailable;
    }

    /**
     * @return mixed
     */
    public function removePassager(User $passager)
    {
        return $this->passagers->removeElement($passager);
    }

    /**
     * @return mixed
     */
    public function getTarif()
    {
        return $this->tarif;
    }

    /**
     * @return mixed
     */
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

        return $this;
    }

    /**
     * @param mixed $tarif
     */
    public function setTarif($tarif)
    {
        $this->tarif = $tarif;

        return $this;
    }

    /**
     * @return mixed
     */
    public function estPlein() {
        return $this->passagers->count() >= $this->passagersMax;
    }

    /**
     * @return bool
     */
    public function estCreateur(User $user = null) {
        return $user && $user->getId() === $this->getCreateur()->getId();
    }
}