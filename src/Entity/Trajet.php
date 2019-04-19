<?php


namespace App\Entity;

use Doctrine\ORM\Mapping as ORM;
use Doctrine\Common\Collections\ArrayCollection;
use Symfony\Component\Validator\Constraints as Assert;

/**
 * @ORM\Entity
 * @ORM\Table(name="trajet")
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
     * Assert\Valid()
     */
    private $creator;

    /**
     * @ORM\Column(type="text")
     * Assert\Length(min=1)
     */
    private $lieuDepart;

    /**
     * @ORM\Column(type="text")
     * Assert\Length(min=1)
     */
    private $lieuArrive;

    /**
     * @ORM\Column(type="datetime")
     * @Assert\DateTime()
     * @Assert\GreaterThan(
     *     "+5 minutes",
     *     message = "Merci de renseigner une date valable (au moins 5 minutes après la date actuelle)")

     */
    private $heureDepart;

    /**
     * @ORM\Column(type="integer")
     * @Assert\Range(
     *      min = 1,
     *      minMessage = "Merci de renseigner un chiffre strictement supérieur à 0"
     * )
     */
    private $passagersMax;

    /**
     * @ORM\ManyToMany(targetEntity="User", cascade={"persist"})
     */
    private $passagers;

    /**
     * @ORM\Column(type="decimal", precision=7, scale=2)
     * @Assert\Range(
     *     max = 50,
     *     maxMessage = "La somme doit être inférieure à 50€"
     *  )
     */
    private $tarif;

    public function __construct()
    {
        $this->passagers = new ArrayCollection();
    }

    /**
     * @return mixed
     */
    public function getCreator()
    {
        return $this->creator;
    }

    /**
     * @param mixed $creator
     */
    public function setCreator(User $creator)
    {
        $this->creator = $creator;
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
    }

    /**
     * @return mixed
     */
    public function getPassagers()
    {
        return $this->passagers;
    }

    public function addPassager(User $passager)
    {
        $newPassagerAvailable = ($this->passagers->count() < $this->passagersMax);
        if ($newPassagerAvailable) {
            if ($this->passagers->contains($passager)) { return 0; }

            $this->passagers[] = $passager;
        }

        return $newPassagerAvailable;
    }

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
     * @param mixed $tarif
     */
    public function setTarif($tarif)
    {
        $this->tarif = $tarif;
    }
}