<?php

namespace Learn\Tests;

use PHPUnit\Framework\TestCase;
use Utils\TestConstants;
use Learn\Entity\Course;
use Learn\Entity\Comment;
use Utils\Exceptions\EntityDataIntegrityException;
use Utils\Exceptions\EntityOperatorException;
use User\Entity\User;

class CommentTest extends TestCase
{
   public function testIdIsSet()
   {
      $comment = new Comment();
      $comment->setId(TestConstants::TEST_INTEGER); // right argument
      $this->assertEquals($comment->getId(), TestConstants::TEST_INTEGER);
      $this->expectException(EntityDataIntegrityException::class);
      $comment->setId(-1); // negative
      $comment->setId(true); // boolean
      $comment->setId(null); // null
   }

   public function testTutorialIsSet()
   {
      $tutorial = new Course();
      $comment = new Comment();
      $comment->setTutorial($tutorial); // right argument
      $this->assertEquals($comment->getTutorial(), $tutorial);
      $this->expectException(EntityDataIntegrityException::class);
      $comment->setTutorial(TestConstants::TEST_INTEGER); // integer
      $comment->setTutorial(true); // boolean
      $comment->setTutorial(null); // null
   }

   public function testUserIsSet()
   {
      $user = new User();
      $comment = new Comment();
      $comment->setUser($user); // right argument
      $this->assertEquals($comment->getUser(), $user);
      $this->expectException(EntityDataIntegrityException::class);
      $comment->setUser(TestConstants::TEST_INTEGER); // integer
      $comment->setUser(true); // boolean
      $comment->setUser(null); // null
   }

   public function testCommentAnsweredIsSet()
   {
      $commentAnswered = new Comment();
      $comment = new Comment();
      $comment->setCommentAnswered($commentAnswered); // right argument
      $this->assertEquals($comment->getCommentAnswered(), $commentAnswered);
      $this->expectException(EntityDataIntegrityException::class);
      $comment->setCommentAnswered(TestConstants::TEST_INTEGER); // integer
      $comment->setCommentAnswered(true); // boolean
      $comment->setCommentAnswered(null); // null
   }

   public function testMessageIsSet()
   {
      $comment = new Comment();

      $acceptedMessage = 'aaaa';
      $nonAcceptedMessage = '';
      for ($i = 0; $i <= TestConstants::MESSAGE_MAX_LENGTH; $i++) //add more than 1000 characters 
         $nonAcceptedMessage .= 'a';

      $comment->setMessage($acceptedMessage); // right argument
      $this->assertEquals($comment->getMessage(), $acceptedMessage);
      $this->expectException(EntityDataIntegrityException::class);
      $comment->setMessage(TestConstants::TEST_INTEGER); // integer
      $comment->setMessage(true); // boolean
      $comment->setMessage(null); // null
      $comment->setMessage($nonAcceptedMessage); // null
   }

   public function testDateIsSet()
   {
      $comment = new Comment();
      $date = new \DateTime('now');
      $comment->setCreatedAt($date); // can be null
      $this->assertEquals($comment->getCreatedAt(), $date);
      $this->expectException(EntityDataIntegrityException::class);
      $comment->setCreatedAt(11); // should not be integer
      $comment->setCreatedAt(TestConstants::TEST_STRING); // should not be a string
      $comment->setCreatedAt(null); // can be null
   }
}
