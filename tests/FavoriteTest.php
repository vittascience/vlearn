<?php

namespace Learn\Tests;

use PHPUnit\Framework\TestCase;
use Utils\TestConstants;
use Learn\Entity\Favorite;
use Learn\Entity\Course;
use Utils\Exceptions\EntityDataIntegrityException;
use Utils\Exceptions\EntityOperatorException;
use User\Entity\User;

class FavoriteTest extends TestCase
{
      public function testIdIsSet()
      {
            $user = new User();
            $tutorial = new Course();
            $chapter = new Favorite($user, $tutorial);
            $chapter->setId(TestConstants::TEST_INTEGER); // right argument
            $this->assertEquals($chapter->getId(), TestConstants::TEST_INTEGER);
            $this->expectException(EntityDataIntegrityException::class);
            $chapter->setId(-1); // negative
            $chapter->setId(true); // boolean
            $chapter->setId(null); // null
      }

      public function testUserIsSet()
      {
            $user = new User();
            $tutorial = new Course();
            $chapter = new Favorite($user, $tutorial);
            $chapter->setUser($user); // right argument
            $this->assertEquals($chapter->getUser(), $user);
            $this->expectException(EntityDataIntegrityException::class);
            $chapter->setUser(TestConstants::TEST_INTEGER); // integer
            $chapter->setUser(true); // boolean
            $chapter->setUser(null); // null
      }

      public function testTutorialIsSet()
      {
            $user = new User();
            $tutorial = new Course();
            $chapter = new Favorite($user, $tutorial);
            $chapter->setTutorial($tutorial); // right argument
            $this->assertEquals($chapter->getTutorial(), $tutorial);
            $this->expectException(EntityDataIntegrityException::class);
            $chapter->setTutorial(TestConstants::TEST_INTEGER); // integer
            $chapter->setTutorial(true); // boolean
            $chapter->setTutorial(null); // null
      }
}
