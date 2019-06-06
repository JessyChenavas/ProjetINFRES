<?php

namespace App\Entity;

use Doctrine\ORM\Mapping as ORM;
use Doctrine\Common\Collections\ArrayCollection;
use Symfony\Component\Validator\Constraints as Assert;

/**
 * @ORM\Entity (repositoryClass="App\Repository\ConversationRepository")
 */
class Conversation
{
    /**
     * @ORM\Id
     * @ORM\Column(type="integer")
     * @ORM\GeneratedValue(strategy="AUTO")
     */
    private $id;

    /**
     * @ORM\ManyToMany(targetEntity="User", cascade={"persist"}, fetch="EAGER")
     * @ORM\JoinColumn(nullable=false)
     * @Assert\Valid()
     */
    private $participants;

    /**
     * @ORM\OneToMany(targetEntity="Message", mappedBy="conversation")
     * @ORM\OrderBy({"date" = "DESC"})
     * @Assert\Valid()
     */
    private $messages;

    /**
     * Conversation constructor.
     */
    public function __construct()
    {
        $this->messages = new ArrayCollection();
        $this->participants = new ArrayCollection();
    }

    /**
     * @return mixed
     */
    public function getMessages()
    {
        return $this->messages;
    }

    public function addMessage(Message $message) {
        $this->messages[] = $message;

        return $this;
    }

    /**
     * @return mixed
     */
    public function getParticipants()
    {
        return $this->participants;
    }

    public function addParticipant(User $user) {
        $this->participants[] = $user;

        return $this;
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

    public function estMembre(User $user) {
        return $this->getParticipants()->contains($user);
    }
}