<?php

namespace Learn\Tests;

use PHPUnit\Framework\TestCase;
use Utils\TestConstants;
use Learn\Entity\Lesson;
use Learn\Entity\Course;
use Learn\Entity\Chapter;
use Utils\Exceptions\EntityDataIntegrityException;
use Utils\Exceptions\EntityOperatorException;

class LessonTest extends TestCase
{
   public function testIdIsSet()
   {
      $lesson = new Lesson();
      $lesson->setId(TestConstants::TEST_INTEGER); // right argument
      $this->assertEquals($lesson->getId(), TestConstants::TEST_INTEGER);
      $this->expectException(EntityDataIntegrityException::class);
      $lesson->setId(-1); // negative
      $lesson->setId(true); // boolean
      $lesson->setId(null); // null
   }

   public function testChapterIsSet()
   {
      $chapter = new Chapter();
      $lesson = new Lesson();
      $lesson->setChapter($chapter); // right argument
      $this->assertEquals($lesson->getChapter(), $chapter);
      $this->expectException(EntityDataIntegrityException::class);
      $lesson->setChapter(TestConstants::TEST_INTEGER); // integer
      $lesson->setChapter(true); // boolean
      $lesson->setChapter(null); // null
   }

   public function testTutorialIsSet()
   {
      $tutorial = new Course();
      $lesson = new Lesson();
      $lesson->setCourse($tutorial); // right argument
      $this->assertEquals($lesson->getCourse(), $tutorial);
      $this->expectException(EntityDataIntegrityException::class);
      $lesson->setCourse(TestConstants::TEST_INTEGER); // integer
      $lesson->setCourse(true); // boolean
      $lesson->setCourse(null); // null
   }
}
