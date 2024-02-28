<?php

declare(strict_types=1);

trait ColorHelper
{
    protected function HexToRGB($value)
    {
        $RGB = [];
        $RGB[0] = (($value >> 16) & 0xFF);
        $RGB[1] = (($value >> 8) & 0xFF);
        $RGB[2] = ($value & 0xFF);
        $this->SendDebug('HexToRGB', 'R: ' . $RGB[0] . ' G: ' . $RGB[1] . ' B: ' . $RGB[2], 0);
        return $RGB;
    }

    protected function RGBToHex($r, $g, $b)
    {
        return ($r << 16) + ($g << 8) + $b;
    }

    protected function HUE2RGB($p, $q, $t)
    {
        if ($t < 0) {
            $t += 1;
        }
        if ($t > 1) {
            $t -= 1;
        }
        if ($t < 1 / 6) {
            return $p + ($q - $p) * 6 * $t;
        }
        if ($t < 1 / 2) {
            return $q;
        }
        if ($t < 2 / 3) {
            return $p + ($q - $p) * (2 / 3 - $t) * 6;
        }
        return $p;
    }

    protected function HSLToRGB($h, $s, $l)
    {
        if ($s == 0) {
            $r = $l;
            $g = $l;
            $b = $l; // achromatic
        } else {
            $q = $l < 0.5 ? $l * (1 + $s) : $l + $s - $l * $s;
            $p = 2 * $l - $q;
            $r = $this->HUE2RGB($p, $q, $h + 1 / 3);
            $g = $this->HUE2RGB($p, $q, $h);
            $b = $this->HUE2RGB($p, $q, $h - 1 / 3);
        }
        $r = round($r * 255);
        $g = round($g * 255);
        $b = round($b * 255);

        $color = sprintf('#%02x%02x%02x', $red, $green, $blue);
        return $color;
        //return array(round($r * 255), round($g * 255), round($b * 255));
    }

    protected function convertHSL($h, $s, $l, $toHex = true)
    {
        $h /= 360;
        $s /= 100;
        $l /= 100;

        $r = $l;
        $g = $l;
        $b = $l;
        $v = ($l <= 0.5) ? ($l * (1.0 + $s)) : ($l + $s - $l * $s);
        if ($v > 0) {
            $m;
            $sv;
            $sextant;
            $fract;
            $vsf;
            $mid1;
            $mid2;

            $m = $l + $l - $v;
            $sv = ($v - $m) / $v;
            $h *= 6.0;
            $sextant = floor($h);
            $fract = $h - $sextant;
            $vsf = $v * $sv * $fract;
            $mid1 = $m + $vsf;
            $mid2 = $v - $vsf;

            switch ($sextant) {
                    case 0:
                          $r = $v;
                          $g = $mid1;
                          $b = $m;
                          break;
                    case 1:
                          $r = $mid2;
                          $g = $v;
                          $b = $m;
                          break;
                    case 2:
                          $r = $m;
                          $g = $v;
                          $b = $mid1;
                          break;
                    case 3:
                          $r = $m;
                          $g = $mid2;
                          $b = $v;
                          break;
                    case 4:
                          $r = $mid1;
                          $g = $m;
                          $b = $v;
                          break;
                    case 5:
                          $r = $v;
                          $g = $m;
                          $b = $mid2;
                          break;
              }
        }
        $r = round($r * 255, 0);
        $g = round($g * 255, 0);
        $b = round($b * 255, 0);

        if ($toHex) {
            $r = ($r < 15) ? '0' . dechex($r) : dechex($r);
            $g = ($g < 15) ? '0' . dechex($g) : dechex($g);
            $b = ($b < 15) ? '0' . dechex($b) : dechex($b);
            return "#$r$g$b";
        } else {
            return "rgb($r, $g, $b)";
        }
    }

    protected function hsv2rgb($hue, $sat, $val)
    {
        $rgb = [0, 0, 0];
        //calc rgb for 100% SV, go +1 for BR-range
        for ($i = 0; $i < 4; $i++) {
            if (abs($hue - $i * 120) < 120) {
                $distance = max(60, abs($hue - $i * 120));
                $rgb[$i % 3] = 1 - (($distance - 60) / 60);
            }
        }
        //desaturate by increasing lower levels
        $max = max($rgb);
        $factor = 255 * ($val / 100);
        for ($i = 0; $i < 3; $i++) {
            //use distance between 0 and max (1) and multiply with value
            $rgb[$i] = round(($rgb[$i] + ($max - $rgb[$i]) * (1 - $sat / 100)) * $factor);
        }
        $rgb['hex'] = sprintf('#%02X%02X%02X', $rgb[0], $rgb[1], $rgb[2]);
        return $rgb;
    }

    protected function RGBtoHSV($R, $G, $B)    // RGB values:    0-255, 0-255, 0-255
    {                                // HSV values:    0-360, 0-100, 0-100
          // Convert the RGB byte-values to percentages
          $R = ($R / 255);
        $G = ($G / 255);
        $B = ($B / 255);

        // Calculate a few basic values, the maximum value of R,G,B, the
        //   minimum value, and the difference of the two (chroma).
        $maxRGB = max($R, $G, $B);
        $minRGB = min($R, $G, $B);
        $chroma = $maxRGB - $minRGB;

        // Value (also called Brightness) is the easiest component to calculate,
        //   and is simply the highest value among the R,G,B components.
        // We multiply by 100 to turn the decimal into a readable percent value.
        $computedV = 100 * $maxRGB;

        // Special case if hueless (equal parts RGB make black, white, or grays)
        // Note that Hue is technically undefined when chroma is zero, as
        //   attempting to calculate it would cause division by zero (see
        //   below), so most applications simply substitute a Hue of zero.
        // Saturation will always be zero in this case, see below for details.
        if ($chroma == 0) {
            return [0, 0, $computedV];
        }

        // Saturation is also simple to compute, and is simply the chroma
        //   over the Value (or Brightness)
        // Again, multiplied by 100 to get a percentage.
        $computedS = 100 * ($chroma / $maxRGB);

        // Calculate Hue component
        // Hue is calculated on the "chromacity plane", which is represented
        //   as a 2D hexagon, divided into six 60-degree sectors. We calculate
        //   the bisecting angle as a value 0 <= x < 6, that represents which
        //   portion of which sector the line falls on.
        if ($R == $minRGB) {
            $h = 3 - (($G - $B) / $chroma);
        } elseif ($B == $minRGB) {
            $h = 1 - (($R - $G) / $chroma);
        } else { // $G == $minRGB
            $h = 5 - (($B - $R) / $chroma);
        }

        // After we have the sector position, we multiply it by the size of
        //   each sector's arc (60 degrees) to obtain the angle in degrees.
        $computedH = 60 * $h;

        return [round($computedH), round($computedS), round($computedV)];
    }

    protected function xyToHEX($x, $y, $bri)
    {

        // Calculate XYZ values
        $z = 1 - $x - $y;
        $Y = $bri / 254; // Brightness coeff.
        if ($y == 0) {
            $X = 0;
            $Z = 0;
        } else {
            $X = ($Y / $y) * $x;
            $Z = ($Y / $y) * $z;
        }

        // Convert to sRGB D65 (official formula on meethue)
        // old formula
        // $r = $X * 3.2406 - $Y * 1.5372 - $Z * 0.4986;
        // $g = - $X * 0.9689 + $Y * 1.8758 + $Z * 0.0415;
        // $b = $X * 0.0557 - $Y * 0.204 + $Z * 1.057;
        // formula 2016
        $r = $X * 1.656492 - $Y * 0.354851 - $Z * 0.255038;
        $g = -$X * 0.707196 + $Y * 1.655397 + $Z * 0.036152;
        $b = $X * 0.051713 - $Y * 0.121364 + $Z * 1.011530;

        // Apply reverse gamma correction
        $r = ($r <= 0.0031308 ? 12.92 * $r : (1.055) * pow($r, (1 / 2.4)) - 0.055);
        $g = ($g <= 0.0031308 ? 12.92 * $g : (1.055) * pow($g, (1 / 2.4)) - 0.055);
        $b = ($b <= 0.0031308 ? 12.92 * $b : (1.055) * pow($b, (1 / 2.4)) - 0.055);

        // Calculate final RGB
        $r = ($r < 0 ? 0 : round($r * 255));
        $g = ($g < 0 ? 0 : round($g * 255));
        $b = ($b < 0 ? 0 : round($b * 255));

        $r = ($r > 255 ? 255 : $r);
        $g = ($g > 255 ? 255 : $g);
        $b = ($b > 255 ? 255 : $b);

        // Create a web RGB string (format #xxxxxx)
        $this->SendDebug('RGB', 'R: ' . $r . ' G: ' . $g . ' B: ' . $b, 0);

        //$RGB = "#".substr("0".dechex($r),-2).substr("0".dechex($g),-2).substr("0".dechex($b),-2);
        $color = sprintf('#%02x%02x%02x', $r, $g, $b);

        return $color;
    }

    protected function RGBToXy($RGB)
    {
        // Get decimal RGB
        $RGB = sprintf('#%02x%02x%02x', $RGB[0], $RGB[1], $RGB[2]);
        $r = hexdec(substr($RGB, 1, 2));
        $g = hexdec(substr($RGB, 3, 2));
        $b = hexdec(substr($RGB, 5, 2));

        // Calculate rgb as coef
        $r = $r / 255;
        $g = $g / 255;
        $b = $b / 255;

        // Apply gamma correction
        $r = ($r > 0.04045 ? pow(($r + 0.055) / 1.055, 2.4) : ($r / 12.92));
        $g = ($g > 0.04045 ? pow(($g + 0.055) / 1.055, 2.4) : ($g / 12.92));
        $b = ($b > 0.04045 ? pow(($b + 0.055) / 1.055, 2.4) : ($b / 12.92));

        // Convert to XYZ (official formula on meethue)
        // old formula
        //$X = $r * 0.649926 + $g * 0.103455 + $b * 0.197109;
        //$Y = $r * 0.234327 + $g * 0.743075 + $b * 0.022598;
        //$Z = $r * 0        + $g * 0.053077 + $b * 1.035763;
        // formula 2016
        $X = $r * 0.664511 + $g * 0.154324 + $b * 0.162028;
        $Y = $r * 0.283881 + $g * 0.668433 + $b * 0.047685;
        $Z = $r * 0.000088 + $g * 0.072310 + $b * 0.986039;

        // Calculate xy and bri
        if (($X + $Y + $Z) == 0) {
            $x = 0;
            $y = 0;
        } else { // round to 4 decimal max (=api max size)
            $x = round($X / ($X + $Y + $Z), 4);
            $y = round($Y / ($X + $Y + $Z), 4);
        }
        $bri = round($Y * 254);
        if ($bri > 254) {
            $bri = 254;
        }

        $cie['x'] = $x;
        $cie['y'] = $y;
        $cie['bri'] = $bri;
        return $cie;
    }
}