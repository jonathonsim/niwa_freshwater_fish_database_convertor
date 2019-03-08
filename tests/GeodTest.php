<?php

use PHPUnit\Framework\TestCase;

class GeodTest extends TestCase {

    public function testNzmgToNzGd1949() {
        $nzcoords = new \App\Services\NzCoordinates();
        $coords = $nzcoords->nzmgToNzGd1949(6751049.719, 2487100.638);
        $this->assertTrue( abs(-34.444066 -  $coords[0] ) < 0.01);
        $this->assertTrue( abs(172.739194 -  $coords[1] ) < 0.01 );

    }

    public function testNzGd1949ToNzmg() {
        $nzcoords = new \App\Services\NzCoordinates();
        $coords = $nzcoords->nzGd1949ToNzmg(-34.444066 , 172.739194);
        $this->assertTrue( abs(6751049.719 -  $coords[0] ) < 0.1);
        $this->assertTrue( abs(2487100.638 -  $coords[1] ) < 0.1 );


        //What is 1 degree difference in NZMG?
        $coords1 = $nzcoords->nzGd1949ToNzmg(-34 , 172);
        $coords2 = $nzcoords->nzGd1949ToNzmg(-33 , 171);

        $northing_difference = abs($coords1[0] -  $coords2[0] );
        $easting_difference = abs($coords1[1] -  $coords2[1] );
        $this->assertNotNull($northing_difference);
        $this->assertNotNull($easting_difference);

    }
}