<?php

namespace Tests\Unit\Depot;

use App\Services\Depot\AnalyseImage;
use PHPUnit\Framework\TestCase;
use RuntimeException;

class AnalyseImageTest extends TestCase
{
    /** @var list<string> */
    private array $fichiers = [];

    protected function tearDown(): void
    {
        array_map('unlink', $this->fichiers);
        parent::tearDown();
    }

    public function test_plain_image_gives_its_colour_and_is_plain(): void
    {
        $resultat = (new AnalyseImage)->analyser($this->image(fn ($img) => $this->remplir($img, [45, 95, 205])));

        $this->assertSame(['couleur' => 'bleu', 'part' => 100, 'uni' => true], $resultat);
    }

    public function test_white_background_is_ignored(): void
    {
        $chemin = $this->image(function ($img) {
            $this->remplir($img, [250, 250, 250]);
            imagefilledrectangle($img, 25, 20, 74, 79, imagecolorallocate($img, 200, 30, 40));
        });

        $resultat = (new AnalyseImage)->analyser($chemin);

        $this->assertSame('rouge', $resultat['couleur']);
        $this->assertTrue($resultat['uni']);
    }

    public function test_striped_image_is_not_plain(): void
    {
        $chemin = $this->image(function ($img) {
            $couleurs = [[20, 20, 20], [240, 210, 40], [50, 140, 60]];
            for ($y = 0; $y < 100; $y += 10) {
                [$r, $g, $b] = $couleurs[($y / 10) % 3];
                imagefilledrectangle($img, 0, $y, 99, $y + 9, imagecolorallocate($img, $r, $g, $b));
            }
        });

        $this->assertFalse((new AnalyseImage)->analyser($chemin)['uni']);
    }

    public function test_unreadable_file_throws(): void
    {
        $chemin = tempnam(sys_get_temp_dir(), 'tc');
        $this->fichiers[] = $chemin;
        file_put_contents($chemin, 'pas une image');

        $this->expectException(RuntimeException::class);
        (new AnalyseImage)->analyser($chemin);
    }

    private function image(callable $dessiner): string
    {
        $img = imagecreatetruecolor(100, 100);
        $dessiner($img);
        $chemin = tempnam(sys_get_temp_dir(), 'tc');
        imagepng($img, $chemin);
        $this->fichiers[] = $chemin;

        return $chemin;
    }

    /**
     * @param  array{int, int, int}  $rgb
     */
    private function remplir(\GdImage $img, array $rgb): void
    {
        imagefilledrectangle($img, 0, 0, 99, 99, imagecolorallocate($img, ...$rgb));
    }
}
