<?php

/*
 * This file is part of the Symfony package.
 *
 * (c) Fabien Potencier <fabien@symfony.com>
 *
 * For the full copyright and license information, please view the LICENSE
 * file that was distributed with this source code.
 */

namespace AppBundle\DataFixtures\ORM;

use AppBundle\Entity\Category;
use AppBundle\Entity\User;
use AppBundle\Entity\Post;
use AppBundle\Entity\Comment;
use AppBundle\Entity\Vote;
use Doctrine\Common\DataFixtures\FixtureInterface;
use Doctrine\Common\Persistence\ObjectManager;
use Symfony\Component\DependencyInjection\ContainerAwareInterface;
use Symfony\Component\DependencyInjection\ContainerInterface;

/**
 * Defines the sample data to load in the database when running the unit and
 * functional tests. Execute this command to load the data:
 *
 *   $ php app/console doctrine:fixtures:load
 *
 * See http://symfony.com/doc/current/bundles/DoctrineFixturesBundle/index.html
 *
 * @author Ryan Weaver <weaverryan@gmail.com>
 * @author Javier Eguiluz <javier.eguiluz@gmail.com>
 */
class LoadFixtures implements FixtureInterface, ContainerAwareInterface
{
    /** @var ContainerInterface */
    private $container;

    public function load(ObjectManager $manager)
    {
        $this->loadUsers($manager);
        $this->loadPosts($manager);
    }

    private function loadUsers(ObjectManager $manager)
    {
        $passwordEncoder = $this->container->get('security.password_encoder');

        $johnUser = new User();
        $johnUser->setUsername('Denis Kiprushev');
        $johnUser->setEmail('d.kiprushev@levi9.com');
        $encodedPassword = $passwordEncoder->encodePassword($johnUser, 'kitten');
        $johnUser->setPassword($encodedPassword);
        $johnUser->setUuid('0B353BD-A89E-475E-922E-FG26FC542824');
        $manager->persist($johnUser);

        $annaAdmin = new User();
        $annaAdmin->setUsername('Alex Martynenko');
        $annaAdmin->setEmail('a.martynenko@levi9.com');
        $annaAdmin->setRoles(array('ROLE_ADMIN'));
        $encodedPassword = $passwordEncoder->encodePassword($annaAdmin, 'kitten');
        $annaAdmin->setPassword($encodedPassword);
        $annaAdmin->setUuid('0B353BD-A89E-475E-922E-FG26FC542825');
        $manager->persist($annaAdmin);

        $manager->flush();
    }

    private function loadPosts(ObjectManager $manager)
    {
        $category = new Category();
        $category->setName('Improvements');

        foreach (range(1, 10) as $i) {
            $post = new Post();

            $post->setTitle($this->getRandomPostTitle());
            $post->setSummary($this->getRandomPostSummary());
            $post->setSlug($this->container->get('slugger')->slugify($post->getTitle()));
            $post->setContent($this->getPostContent());
            $post->setAuthorEmail('a.martynenko@levi9.com');
            $post->setPublishedAt(new \DateTime('now - '.$i.'days'));
            $post->setState($this->getRandomState());
            $post->setCategory($category);

            foreach (range(1, 5) as $j) {
                $comment = new Comment();

                $comment->setAuthorEmail('d.kiprushev@levi9.com');
                $comment->setPublishedAt(new \DateTime('now + '.($i + $j).'seconds'));
                $comment->setContent($this->getRandomCommentContent());
                $comment->setPost($post);

                $manager->persist($comment);
                $post->addComment($comment);
            }

            if (rand(0, 1)) {
                $vote = new Vote();
                $vote->setAuthorEmail(rand(0, 1) ? 'a.martynenko@levi9.com' : 'd.kiprushev@levi9.com');
                $vote->setPost($post);
                $vote->setVote(rand(0 ,1));
            }

            $manager->persist($post);
            $category->addPost($post);
        }

        $manager->flush();
    }

    public function setContainer(ContainerInterface $container = null)
    {
        $this->container = $container;
    }

    private function getPostContent()
    {
        return <<<MARKDOWN
Всем привет!
Есть предложение покататься на байдарках в выходные.
Ночевка в палатке, комары и прочие радости отсутствия цивилизации!
Компания оплачивает услуги агенства, куда входят: дорога, питание, аренда байдарок, лодок и т.д.
От нас: желание отлично провести время не покалечившись.

![Image of Yaktocat](https://www.levi9.com/wp-content/themes/levi9/imgs/logo.png)

Просьба проголосовать за дату, которая бы подошла вам. Если обе даты вам подходят – ставьте крестик под обеими датами, соответственно.

![Image of Yaktocat](http://wildtraveler.com.ua/trash/statica/538/IMG_0528-1.jpg)

https://docs.google.com/spreadsheets/d/19i2BAoHeGYNM9JczxDcXSYGYhBa7NiPfzmwcYy8p3A4/edit#gid=0

Для того чтобы вы не путались в своих собственных отгулах, мы добавили запрет на создание пересекающихся реквестов. Мы надеемся что это окончательно упростит вашу работу с системой. Также внесли ясность в  использование дней категории «Personal events». Как вы уже знаете, у каждого из сотрудников есть три дополнительных дня отгула для важных событий в вашей жизни, такие как:  Wedding/ Child Birth/Death related. Что значит, что вы можете использовать по одному дню каждого типа.
В системе они отображаются соответствующим образом: 1/1/1 .

Если есть вопросы – обращайтесь ко мне или Жене Черне.

MARKDOWN;
    }

    private function getPhrases()
    {
        return array(
            'Будет воллейбол и футбол, поэтому берите купальники и плавки ;)',
            'Ближе к дате напишу все более подробно и отвечу на все ваши вопросы.',
            'Небольшое обновление по работе системы timeoff-ua.levi9.com',
            'Очередной обучающий ликбез, в этот раз - по PHP.',
            'Иван Трофименко, Senior PHP Developer at Levi9: Diving into Dependency Injection',
            'ндрей Завадский, Department Manager, PHP Architect at Levi9: Microservices',
            'Ночевка в палатке, комары и прочие радости отсутствия цивилизации!',
            'Будем есть мясо (но не я), пить шампанское',
            'Риквест на новые джойстики Sony PS3',
            'Собираемся на футбол командой Levi9'
        );
    }

    private function getRandomPostTitle()
    {
        $titles = $this->getPhrases();

        return $titles[array_rand($titles)];
    }

    private function getRandomPostSummary()
    {
        $phrases = $this->getPhrases();

        $numPhrases = rand(6, 12);
        shuffle($phrases);

        return implode(' ', array_slice($phrases, 0, $numPhrases-1));
    }

    private function getRandomCommentContent()
    {
        $phrases = $this->getPhrases();

        $numPhrases = rand(2, 15);
        shuffle($phrases);

        return implode(' ', array_slice($phrases, 0, $numPhrases-1));
    }

    private function getRandomState()
    {
        return rand(Post::STATUS_DRAFT, Post::STATUS_REJECTED);
    }
}
