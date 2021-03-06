<?php

namespace App\Entity;

use Doctrine\ORM\Mapping as ORM;
use Doctrine\Common\Collections\ArrayCollection;
use Symfony\Component\Validator\Constraints as Assert;
use Hateoas\Configuration\Annotation as Hateoas;
use JMS\Serializer\Annotation as Serializer;

/**
 * @ORM\Entity
 *
 * @Hateoas\Relation("self",
 *      href = @Hateoas\Route("api_afficher_annonce", parameters = { "id" = "expr(object.getId())" }))

 * @Hateoas\Relation("edit",
 *      href = @Hateoas\Route("api_modifier_annonce", parameters = { "id" = "expr(object.getId())" }))
 *
 * @Hateoas\Relation("delete",
 *      href = @Hateoas\Route("api_supprimer_annonce", parameters = { "id" = "expr(object.getId())" }))
 *
 *  @Hateoas\Relation("images", embedded = "expr(object.getImages())",
 *   exclusion = @Hateoas\Exclusion(excludeIf = "expr(object.getImages() === null)"))
 *
 *  @Hateoas\Relation("createur", embedded = "expr(object.getCreateur())",
 *   exclusion = @Hateoas\Exclusion(excludeIf = "expr(object.getCreateur() === null)"))
 */
class Annonce
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
     * @Assert\NotBlank(message="Merci de renseigner un titre !")
     */
    private $titre;

    /**
     * @ORM\Column(type="text")
     * @Assert\NotBlank(message="Merci de renseigner une description !")
     */
    private $description;

    /**
     * @ORM\ManyToMany(targetEntity="Image", cascade={"persist"}, fetch="EAGER")
     * @Assert\Valid()
     *
     * @Serializer\Exclude()
     */
    private $images;

    /**
     * @ORM\Column(type="decimal", precision=7, scale=2)
     * @Assert\Range(
     *     min = 0,
     *     minMessage = "La somme ne peut pas être négative !"
     *  )
     */
    private $prix;

    /**
     * Annonce constructor.
     */
    public function __construct() {
        $this->images = new ArrayCollection();
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
    public function setCreateur($createur)
    {
        $this->createur = $createur;

        return $this;
    }

    public function estCreateur(User $user = null) {
        return $user && $user->getId() === $this->getCreateur()->getId();
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

        return $this;
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

        return $this;
    }

    /**
     * @return mixed
     */
    public function getPrix()
    {
        return $this->prix;
    }

    /**
     * @param mixed $prix
     */
    public function setPrix($prix)
    {
        $this->prix = $prix;

        return $this;
    }

    /**
     * @return mixed
     */
    public function getImages()
    {
        return $this->images;
    }

    public function addImage(Image $image)
    {
        if (!$this->getImages()->contains($image)) {
            $this->images[] = $image;
        }
    }

    public function removeImage(Image $image) {
        if ($this->getImages()->contains($image)) {
            $this->images->removeElement($image);
        }
    }
}