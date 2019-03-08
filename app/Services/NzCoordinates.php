<?php

namespace App\Services;

use Complex\Complex;

/**
 * Convert NZMG ('northing' and 'easting') to NZGD1949 map projections ('latitude' and 'longitude')
 * https://www.linz.govt.nz/data/geodetic-system/coordinate-conversion/projection-conversions/new-zealand-map-grid
 *
 * This is ported from the C code downloadable here https://www.linz.govt.nz/data/geodetic-services/download-geodetic-software
 *
 */
class NzCoordinates
{
    static $cache = [];

    private $rad2deg = 180 / 3.1415926535898;
    private $a = 6378388.0;
    private $n0 = 6023150.0;
    private $e0 = 2510000.0;
    private $lt0 = -41.0;
    private $ln0 = 173.0;
    private $cfi = [
        0.6399175073,
        -0.1358797613,
        0.063294409,
        -0.02526853,
        0.0117879,
        -0.0055161,
        0.0026906,
        -0.001333,
        0.00067,
        -0.00034
    ];

    private $cfl = [
        1.5627014243,
        0.5185406398,
        -0.03333098,
        -0.1052906,
        -0.0368594,
        0.007317,
        0.01220,
        0.00394,
        -0.0013
    ];

    private $cfb1 = [];
    private $cfb2 = [];

    public function __construct()
    {


        $this->cfb1 = [
            new Complex(0.7557853228, 0.0),
            new Complex(0.249204646, 0.003371507),
            new Complex(-0.001541739, 0.041058560),
            new Complex(-0.10162907, 0.01727609),
            new Complex(-0.26623489, -0.36249218),
            new Complex(-0.6870983, -1.1651967)
        ];

        $this->cfb2 = [
            new Complex(1.3231270439, 0.0),
            new Complex(-0.577245789, -0.007809598),
            new Complex(0.508307513, -0.112208952),
            new Complex(-0.15094762, 0.18200602),
            new Complex(1.01418179, 1.64497696),
            new Complex(1.9660549, 2.5127645)
        ];
    }

    public function scale(float $scale, Complex $c)
    {
        return new Complex($scale * $c->getReal(), $scale * $c->getImaginary());
    }

    /** Convert NZMG ('northing' and 'easting') to NZGD1949 map projections ('latitude' and 'longitude')
     * https://www.linz.govt.nz/data/geodetic-system/coordinate-conversion/projection-conversions/new-zealand-map-grid
     *
     */
    public function nzmgToNzGd1949($n, $e)
    {
        if (array_key_exists("$n,$e", self::$cache)) {
            return self::$cache["$n,$e"];
        }

        $z0 = new Complex(
            ($n - $this->n0) / $this->a,
            ($e - $this->e0) / $this->a
        );

        $z1 = $this->cfb2[5];

        for ($i = 5; $i--;) {
            $z1 = \Complex\add(\Complex\multiply($z1, $z0), $this->cfb2[$i]);
        }

        $z1 = \Complex\multiply($z1, $z0);

        for ($it = 2; $it--;) {
            $zn = $this->scale(5, $this->cfb1[5]);
            $zd = $this->scale(6, $this->cfb1[5]);
            for ($i = 4; $i; $i--) {
                $zn = \Complex\add(
                    \Complex\multiply($zn, $z1),
                    $this->scale($i, $this->cfb1[$i])
                );
                $zd = \Complex\add(
                    \Complex\multiply($zd, $z1),
                    $this->scale($i + 1, $this->cfb1[$i])
                );
            }
            $zn = \Complex\add($z0,
                \Complex\multiply(
                    \Complex\multiply($zn, $z1),
                    $z1
                ));

            $zd = \Complex\add($this->cfb1[0],
                \Complex\multiply($zd, $z1)
            );
            $z1 = \Complex\divideby($zn, $zd);
        }

        $ln = $this->ln0 / $this->rad2deg + $z1->getImaginary();
        $tmp = $z1->getReal();
        $sum = $this->cfl[8];
        for ($i = 8; $i--;) {
            $sum = $sum * $tmp + $this->cfl[$i];
        }
        $sum *= $tmp / 3600.0e-5;
        $lt = ($this->lt0 + $sum) / $this->rad2deg;

        $result = [$lt * $this->rad2deg, $ln * $this->rad2deg];
        self::$cache["$n,$e"] = $result;
        return $result;
    }

    /** NZGD1949 map projections ('latitude' and 'longitude') to  NZMG ('northing' and 'easting') to
     * https://www.linz.govt.nz/data/geodetic-system/coordinate-conversion/projection-conversions/new-zealand-map-grid
     *
     */
    public function nzGd1949ToNzmg($lt, $ln)
    {

        //The code works in terms of radians
        $lt /= $this->rad2deg;
        $ln /= $this->rad2deg;


        $lt = ($lt * $this->rad2deg - $this->lt0)  * 3600.0e-5;
        $sum = $this->cfi[9];
        for ($i = 9; $i--;) {
            $sum = $sum * $lt + $this->cfi[$i];
        }
        $sum *= $lt;
        $z1 = new Complex($sum, $ln - $this->ln0 / $this->rad2deg);
        $z0 = $this->cfb1[5];
        for ($i = 5; $i--;) {
            $z0 = \Complex\add(\Complex\multiply($z0, $z1), $this->cfb1[$i]);
        }
        $z0 = \Complex\multiply($z0, $z1);
        $n = $this->n0 + $z0->getReal() * $this->a;
        $e = $this->e0 + $z0->getImaginary() * $this->a;
        return [$n, $e];

    }
}

