<?php

namespace Learn\Tests;

use PHPUnit\Framework\TestCase;
use Utils\TestConstants;
use Learn\Entity\Chapter;
use Learn\Entity\Collection;
use Learn\Entity\Lesson;
use Utils\Exceptions\EntityDataIntegrityException;
use Utils\Exceptions\EntityOperatorException;

class ChapterTest extends TestCase
{
   public function testIdIsSet()
   {
      $chapter = new Chapter();
      $chapter->setId(TestConstants::TEST_INTEGER); // right argument
      $this->assertEquals($chapter->getId(), TestConstants::TEST_INTEGER);
      $this->expectException(EntityDataIntegrityException::class);
      $chapter->setId(-1); // negative
      $chapter->setId(true); // boolean
      $chapter->setId(null); // null
   }

   public function testLessonIsSet()
   {
      $lesson = new Lesson();
      $chapter = new Chapter();
      $chapter->setLesson($lesson); // right argument
      $this->assertEquals($chapter->getLesson(), $lesson);
      $this->expectException(EntityDataIntegrityException::class);
      $chapter->setLesson(TestConstants::TEST_INTEGER); // integer
      $chapter->setLesson(true); // boolean
      $chapter->setLesson(null); // null
   }

   public function testCollectionIsSet()
   {
      $collection = new COllection();
      $chapter = new Chapter();
      $chapter->setCollection($collection); // right argument
      $this->assertEquals($chapter->getCollection(), $collection);
      $this->expectException(EntityDataIntegrityException::class);
      $chapter->setCollection(TestConstants::TEST_INTEGER); // integer
      $chapter->setCollection(true); // boolean
      $chapter->setCollection(null); // null
   }
}