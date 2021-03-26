<?php

namespace Learn\Tests;

use PHPUnit\Framework\TestCase;
use Utils\TestConstants;
use Learn\Entity\Chapter;
use Learn\Entity\Collection;
use Utils\Exceptions\EntityDataIntegrityException;
use Utils\Exceptions\EntityOperatorException;

class CollectionTest extends TestCase
{
   public function testIdIsSet()
   {
      $collection = new Collection();
      $collection->setId(TestConstants::TEST_INTEGER); // right argument
      $this->assertEquals($collection->getId(), TestConstants::TEST_INTEGER);
      $this->expectException(EntityDataIntegrityException::class);
      $collection->setId(-1); // negative
      $collection->setId(true); // boolean
      $collection->setId(null); // null
   }

   public function testNameCollectionIsSet()
   {
      $collection = new Collection();
      $acceptedName = 'aaaa';
      $nonAcceptedName = '';
      for ($i = 0; $i <= TestConstants::NAME_COLLECTION_MAX_LENGTH; $i++) //add more than 100 characters 
         $nonAcceptedName .= 'a';

      $collection->setNameCollection($acceptedName); // right argument
      $this->assertEquals($collection->getNameCollection(), $acceptedName);
      $this->expectException(EntityDataIntegrityException::class);
      $collection->setNameCollection(TestConstants::TEST_INTEGER); // integer
      $collection->setNameCollection(true); // boolean
      $collection->setNameCollection(null); // null
      $collection->setNameCollection($nonAcceptedName); // more than 20 chars
   }
   
   public function testGradeCollectionIsSet()
   {
      $collection = new Collection();
      $acceptedName = 'aaaa';
      $nonAcceptedName = '';
      for ($i = 0; $i <= TestConstants::NAME_COLLECTION_MAX_LENGTH; $i++) //add more than 100 characters 
         $nonAcceptedName .= 'a';

      $collection->setGradeCollection($acceptedName); // right argument
      $this->assertEquals($collection->getGradeCollection(), $acceptedName);
      $this->expectException(EntityDataIntegrityException::class);
      $collection->setGradeCollection(TestConstants::TEST_INTEGER); // integer
      $collection->setGradeCollection(true); // boolean
      $collection->setGradeCollection(null); // null
      $collection->setGradeCollection($nonAcceptedName); // more than 20 chars
   }

   public function testCopyIsSet()
   {
      $collection = new Collection();
      $collectionCopy = new Collection();
      $collectionCopy->setNameCollection(TestConstants::TEST_STRING);
      $collection->copy($collectionCopy); // after copying the collections most be equal
      $this->assertEquals($collection, $collectionCopy);
      $this->expectException(EntityOperatorException::class);
      $collection->copy(null); // should not copy a null value
   }

}