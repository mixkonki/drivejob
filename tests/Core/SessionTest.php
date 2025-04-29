<?php
namespace Tests\Core;

use PHPUnit\Framework\TestCase;
use Tests\Mocks\MockSession as Session;

class SessionTest extends TestCase {
    
    protected function setUp(): void {
        // Καθαρισμός της συνεδρίας πριν από κάθε δοκιμή
        Session::destroy();
    }
    
    public function testStart() {
        $this->assertTrue(Session::start());
        $this->assertTrue(Session::isStarted());
    }
    
    public function testSetAndGet() {
        Session::start();
        
        // Δοκιμή με απλή τιμή
        Session::set('test_key', 'test_value');
        $this->assertEquals('test_value', Session::get('test_key'));
        
        // Δοκιμή με πίνακα
        $testArray = ['a' => 1, 'b' => 2];
        Session::set('test_array', $testArray);
        $this->assertEquals($testArray, Session::get('test_array'));
        
        // Δοκιμή με προεπιλεγμένη τιμή
        $this->assertEquals('default', Session::get('non_existent', 'default'));
    }
    
    public function testHas() {
        Session::start();
        
        $this->assertFalse(Session::has('test_key'));
        
        Session::set('test_key', 'test_value');
        $this->assertTrue(Session::has('test_key'));
    }
    
    public function testRemove() {
        Session::start();
        
        Session::set('test_key', 'test_value');
        $this->assertTrue(Session::has('test_key'));
        
        $this->assertTrue(Session::remove('test_key'));
        $this->assertFalse(Session::has('test_key'));
        
        // Αφαίρεση ανύπαρκτου κλειδιού
        $this->assertFalse(Session::remove('non_existent'));
    }
    
    public function testClear() {
        Session::start();
        
        Session::set('key1', 'value1');
        Session::set('key2', 'value2');
        
        $this->assertTrue(Session::clear());
        
        $this->assertFalse(Session::has('key1'));
        $this->assertFalse(Session::has('key2'));
    }
    
    public function testRegenerateId() {
        Session::start();
        
        // Αποθήκευση δεδομένων πριν την αναγέννηση
        Session::set('test_key', 'test_value');
        
        $this->assertTrue(Session::regenerate());
        
        // Βεβαιωθείτε ότι τα δεδομένα διατηρήθηκαν
        $this->assertEquals('test_value', Session::get('test_key'));
    }
    
    public function testDestroy() {
        Session::start();
        
        Session::set('test_key', 'test_value');
        
        $this->assertTrue(Session::destroy());
        
        // Επανεκκίνηση της συνεδρίας για να ελέγξουμε αν τα δεδομένα διαγράφηκαν
        Session::start();
        
        $this->assertFalse(Session::has('test_key'));
    }
}