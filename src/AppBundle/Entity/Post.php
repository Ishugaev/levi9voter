<?php

namespace AppBundle\Entity;

use Doctrine\ORM\Mapping as ORM;
use Doctrine\Common\Collections\ArrayCollection;
use Symfony\Component\Validator\Constraints as Assert;

/**
 * @ORM\Entity(repositoryClass="AppBundle\Repository\PostRepository")
 *
 * Defines the properties of the Post entity to represent the blog posts.
 * See http://symfony.com/doc/current/book/doctrine.html#creating-an-entity-class
 *
 * Tip: if you have an existing database, you can generate these entity class automatically.
 * See http://symfony.com/doc/current/cookbook/doctrine/reverse_engineering.html
 *
 * @author Ryan Weaver <weaverryan@gmail.com>
 * @author Javier Eguiluz <javier.eguiluz@gmail.com>
 */
class Post
{
    /**
     * Use constants to define configuration options that rarely change instead
     * of specifying them in app/config/config.yml.
     * See http://symfony.com/doc/current/best_practices/configuration.html#constants-vs-configuration-options
     */
    const NUM_ITEMS = 10;

    const STATUS_DRAFT = 0;
    const STATUS_REVIEW = 1;
    const STATUS_VOTING = 2;
    const STATUS_APPROVED = 3;
    const STATUS_REJECTED = 4;

    const VOTING_MOST_RATED = 0;
    const VOTING_MOST_POPULAR = 1;

    /**
     * @var int
     *
     * @ORM\Id
     * @ORM\GeneratedValue
     * @ORM\Column(type="integer")
     */
    private $id;

    /**
     * @var string
     *
     * @ORM\Column(type="string")
     * @Assert\NotBlank()
     */
    private $title;

    /**
     * @var string
     *
     * @ORM\Column(type="string", unique=true)
     */
    private $slug;

    /**
     * @var string
     *
     * @ORM\Column(type="string")
     * @Assert\NotBlank(message="post.blank_summary")
     */
    private $summary;

    /**
     * @var string
     *
     * @ORM\Column(type="text")
     * @Assert\Length(min = "10", minMessage = "post.too_short_content")
     */
    private $content;

    /**
     * @var User
     *
     * @ORM\ManyToOne(targetEntity="AppBundle\Entity\User")
     * @ORM\JoinColumn(name="user_id", referencedColumnName="id")
     */
    private $author;

    /**
     * @var \DateTime
     *
     * @ORM\Column(type="datetime")
     * @Assert\DateTime()
     */
    private $publishedAt;

    /**
     * @var ArrayCollection
     *
     * @ORM\OneToMany(
     *      targetEntity="Comment",
     *      mappedBy="post",
     *      orphanRemoval=true
     * )
     * @ORM\OrderBy({"publishedAt" = "DESC"})
     */
    private $comments;

    /**
     * @var Category
     *
     * @ORM\ManyToOne(targetEntity="Category", inversedBy="posts", cascade={"persist"})
     * @ORM\JoinColumn(nullable=false)
     */
    private $category;

    /**
     * @var ArrayCollection
     *
     * @ORM\OneToMany(
     *      targetEntity="Vote",
     *      mappedBy="post",
     *      orphanRemoval=true
     * )
     */
    private $votes;

    /**
     * @var ArrayCollection
     *
     * @ORM\OneToMany(
     *      targetEntity="Image",
     *      mappedBy="post",
     *      orphanRemoval=true
     * )
     */
    private $images;

    /**
     * @var integer
     *
     * @ORM\Column(type="integer", options={"default" = 0})
     */
    private $state = 0;

    public function __construct()
    {
        $this->publishedAt = new \DateTime();
        $this->comments = new ArrayCollection();
        $this->votes = new ArrayCollection();
    }

    /**
     * @return Category
     */
    public function getCategory()
    {
        return $this->category;
    }

    /**
     * @param Category $category
     *
     * @return $this
     */
    public function setCategory(Category $category = null)
    {
        $this->category = $category;

        return $this;
    }

    /**
     * @return int
     */
    public function getId()
    {
        return $this->id;
    }

    /**
     * @return string
     */
    public function getTitle()
    {
        return $this->title;
    }

    /**
     * @param string $title
     *
     * @return $this
     */
    public function setTitle($title)
    {
        $this->title = $title;

        return $this;
    }

    /**
     * @return string
     */
    public function getSlug()
    {
        return $this->slug;
    }

    /**
     * @param string $slug
     *
     * @return $this
     */
    public function setSlug($slug)
    {
        $this->slug = $slug;

        return $this;
    }

    /**
     * @return string
     */
    public function getContent()
    {
        return $this->content;
    }

    /**
     * @param string $content
     *
     * @return $this
     */
    public function setContent($content)
    {
        $this->content = $content;

        return $this;
    }

    /**
     * @return User
     */
    public function getAuthor()
    {
        return $this->author;
    }

    /**
     * @param User $author
     *
     * @return $this
     */
    public function setAuthor(User $author)
    {
        $this->author = $author;

        return $this;
    }

    /**
     * @return mixed
     */
    public function getAuthorEmail()
    {
        return $this->author->getEmail();
    }

    /**
     * Is the given User the author of this Post?
     *
     * @param User $user
     *
     * @return bool
     */
    public function isAuthor(User $user = null)
    {
        return $this->author->getUuid() == $user->getUuid();
    }

    /**
     * @return \DateTime
     */
    public function getPublishedAt()
    {
        return $this->publishedAt;
    }

    /**
     * @param \DateTime $publishedAt
     *
     * @return $this
     */
    public function setPublishedAt(\DateTime $publishedAt)
    {
        $this->publishedAt = $publishedAt;

        return $this;
    }

    /**
     * @return ArrayCollection
     */
    public function getComments()
    {
        return $this->comments;
    }

    /**
     * @param Comment $comment
     *
     * @return $this
     */
    public function addComment(Comment $comment)
    {
        $this->comments->add($comment);
        $comment->setPost($this);

        return $this;
    }

    /**
     * @param Comment $comment
     *
     * @return $this
     */
    public function removeComment(Comment $comment)
    {
        $this->comments->removeElement($comment);
        $comment->setPost(null);

        return $this;
    }

    /**
     * @return ArrayCollection
     */
    public function getVotes()
    {
        return $this->votes;
    }

    /**
     * @param Vote $vote
     *
     * @return $this
     */
    public function addVote(Vote $vote)
    {
        $this->votes->add($vote);
        $vote->setPost($this);

        return $this;
    }

    /**
     * @param Vote $vote
     *
     * @return $this
     */
    public function removeVote(Vote $vote)
    {
        $this->votes->removeElement($vote);
        $vote->setPost(null);

        return $this;
    }

    /**
     * @return ArrayCollection
     */
    public function getImages()
    {
        return $this->images;
    }

    /**
     * @param Image $image
     *
     * @return $this
     */
    public function addImage(Image $image)
    {
        $this->images->add($image);
        $image->setPost($this);

        return $this;
    }

    /**
     * @param Image $image
     *
     * @return $this
     */
    public function removeImage(Image $image)
    {
        $this->images->removeElement($image);
        $image->setPost(null);

        return $this;
    }

    /**
     * @return string
     */
    public function getSummary()
    {
        return $this->summary;
    }

    /**
     * @param string $summary
     *
     * @return $this
     */
    public function setSummary($summary)
    {
        $this->summary = $summary;

        return $this;
    }

    /**
     * @return int
     */
    public function getState()
    {
        return $this->state;
    }

    /**
     * @param int $state
     *
     * @return $this
     */
    public function setState($state)
    {
        $this->state = $state;

        return $this;
    }

    /**
     * @return int
     */
    public function getAgreeCount()
    {
        $agree = 0;
        foreach ($this->votes as $vote) {
            if ($vote->getVote() == Vote::LIKE) {
                $agree++;
            }
        }
        return $agree;
    }

    /**
     * @return int
     */
    public function getNotAgreeCount()
    {
        $notAgree = 0;
        foreach ($this->votes as $vote) {
            if ($vote->getVote() == Vote::DISLIKE) {
                $notAgree++;
            }
        }

        return $notAgree;
    }

    /**
     * @return $this
     */
    public function publish()
    {
        $this->setState(self::STATUS_VOTING);
        $this->setPublishedAt(new \DateTime());

        return $this;
    }

    /**
     * @return bool
     */
    public function isInReview()
    {
        return self::STATUS_REVIEW === $this->getState();
    }
}
