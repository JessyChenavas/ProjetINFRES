<?php

namespace App\Entity;

use FOS\UserBundle\Model\User as BaseUser;
use Doctrine\ORM\Mapping as ORM;
use Symfony\Component\Validator\Constraints as Assert;
use Hateoas\Configuration\Annotation as Hateoas;
use JMS\Serializer\Annotation as Serializer;

/**
 * @ORM\Entity
 * @ORM\Table(name="fos_user")
 *
 * @Hateoas\Relation("self",
 *      href = @Hateoas\Route("api_afficher_utilisateur", parameters = { "id" = "expr(object.getId())" }))
 *
 * @Hateoas\Relation("edit",
 *      href = @Hateoas\Route("api_modifier_utilisateur", parameters = { "id" = "expr(object.getId())" }))
 *
 * @Hateoas\Relation("delete",
 *      href = @Hateoas\Route("api_supprimer_utilisateur", parameters = { "id" = "expr(object.getId())" }))
 *
 * @Hateoas\Relation("send_message",
 *     href = @Hateoas\Route("api_envoyer_message", parameters = { "id" = "expr(object.getId())" }))
 *
 * @Hateoas\Relation("show_agenda",
 *     href = @Hateoas\Route("api_afficher_agenda_utilisateur", parameters = { "id" = "expr(object.getId())" }))
 *
 * @Hateoas\Relation("show_trajets",
 *     href = @Hateoas\Route("api_afficher_trajets_utilisateur", parameters = { "id" = "expr(object.getId())" }))
 *
 *  @Hateoas\Relation("voiture", embedded = "expr(object.getVoiture())",
 *   exclusion = @Hateoas\Exclusion(excludeIf = "expr(object.getVoiture() === null)")
 * )
 */
class User extends BaseUser
{
    /**
     * @ORM\Id
     * @ORM\Column(type="integer")
     * @ORM\GeneratedValue(strategy="AUTO")
     * @Serializer\Groups({"summary", "details"})
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
     *
     * @Serializer\Exclude()
     */
    private $voiture;

    /**
     * @ORM\Column(type="string", nullable=true)
     * @Assert\Regex(
     *     pattern="/^0[1-9]\d{8}$/",
     *     message="Numéro de téléphone invalide"
     * )
     */
    private $telephone;

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

    /**
     * @return mixed
     */
    public function getTelephone()
    {
        return $this->telephone;
    }

    /**
     * @param mixed $telephone
     */
    public function setTelephone($telephone)
    {
        $this->telephone = $telephone;
    }
}