<?php

/*
 * Edit the connection details to the database in the tests setUp function
 *
 * The factory_lib_test database needs a table with the following structure
 *
 CREATE TABLE `person` (
  `id` int(11) NOT NULL AUTO_INCREMENT,
  `forename` varchar(100) DEFAULT NULL,
  `surname` varchar(100) NOT NULL,
  `email_address` varchar(100) DEFAULT NULL,
  `age` int(11) DEFAULT NULL,
  PRIMARY KEY (`id`)
  ) DEFAULT CHARSET=utf8;
 */

require_once 'PHPUnit/Framework.php' ;
require dirname(__FILE__) . "/factory_lib.php" ;

Factory::$factory_data['person'] = array('forename' => 'Jeremy {{counter}}', 'surname' => 'Wilkins', 
																					'email_address' => 'test{{counter}}@testhost.com') ;

class factoryLibTest extends PHPUnit_Framework_TestCase {
	function setUp() {
		mysql_connect() ;
		mysql_select_db('factory_lib_test') ;
		mysql_query("TRUNCATE person") || die("UNABLE TO TRUNCATE PERSON TABLE") ;
		Factory::reset_counter() ;
	}
	
	function tearDown() {
		mysql_close() ;
	}
	
	function testInitialisesValues() {
		$p = new Factory('person') ;
		$this->assertEquals('Jeremy 1', $p->get('forename')) ;
		$this->assertEquals('Wilkins', $p->get('surname')) ;
		$this->assertNull($p->get('age')) ;
		$this->assertEquals('test1@testhost.com', $p->get('email_address')) ;
	}
	
	function testMergingValues() {
		$p = new Factory('person', array('forename' => 'Carl', 'age' => 30)) ;
		$this->assertEquals('Carl', $p->get('forename')) ;
		$this->assertEquals('Wilkins', $p->get('surname')) ;
		$this->assertEquals(30, $p->get('age')) ;
		$this->assertEquals('test1@testhost.com', $p->get('email_address')) ;
	}
	
	function testAccessibleAsArray() {
		$p = new Factory('person') ;
		$this->assertEquals('Wilkins', $p->data['surname']) ;
	}
	
	function testCanStore() {
		$this->assertEquals(0, $this->countPeople()) ;
		$p = new Factory('person') ;
		$this->assertEquals(1, $p->store()) ;
		$this->assertEquals(1, $this->countPeople()) ;
	}
	
	function testCanCreate() {
		$this->assertEquals(0, $this->countPeople()) ;
		Factory::create('person') ;
		$this->assertEquals(1, $this->countPeople()) ;
	}
	
	function testCanGetHash() {
		$a = Factory::hash('person', array('surname' => 'Fowler', 'age' => 26)) ;
		$this->assertEquals('Jeremy 1', $a['forename']) ;
		$this->assertEquals('Fowler', $a['surname']) ;
		$this->assertEquals(26, $a['age']) ;
	}
	
	function testCanCreateMultipleRows() {
		for($i = 0; $i < 4; $i++)
			Factory::create('person') ;
		
		$this->assertNotEquals(false, $this->findPersonByEmail('test1@testhost.com')) ;
		$this->assertNotEquals(false, $this->findPersonByEmail('test2@testhost.com')) ;
		$this->assertNotEquals(false, $this->findPersonByEmail('test3@testhost.com')) ;
		$this->assertNotEquals(false, $this->findPersonByEmail('test4@testhost.com')) ;
	}
	
	function testCanTruncate() {
		Factory::create('person') ;
		$this->assertEquals(1, $this->countPeople()) ;
		
		Factory::truncate('person') ;
		$this->assertEquals(0, $this->countPeople()) ;
	}
	
	private function countPeople() {
		($r = mysql_query("SELECT COUNT(*) AS count FROM person")) || die("UNABLE TO COUNT PEOPLE") ;
		$c = mysql_fetch_assoc($r) ;
		return $c['count'] ;
	}
	
	private function findPersonByEmail($email) {
		$r = mysql_query("SELECT * FROM person WHERE email_address = '{$email}'") ;
		return mysql_fetch_assoc($r) ;
	}
	
}

?>