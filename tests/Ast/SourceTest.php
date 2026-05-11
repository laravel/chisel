<?php

use Illuminate\Contracts\Auth\MustVerifyEmail;
use Laravel\Chisel\Ast\Source;
use Laravel\Chisel\Chisel;

it('removes imports interfaces and traits from php files on destruct', function (): void {
    $path = $this->tempDir.'/User.php';

    file_put_contents($path, <<<'PHP'
<?php

namespace App\Models;

use Illuminate\Database\Eloquent\Model, Illuminate\Contracts\Auth\MustVerifyEmail;
use Laravel\Fortify\TwoFactorAuthenticatable;
use Laravel\Sanctum\HasApiTokens;
use Tests\Fixtures\HasFactory;

class User extends Model implements MustVerifyEmail
{
    use TwoFactorAuthenticatable, HasApiTokens;
    use HasFactory;

    protected $table = 'users';
}
PHP);

    $file = Chisel::in($this->tempDir)
        ->php('User.php')
        ->removeImport(MustVerifyEmail::class)
        ->removeTrait('TwoFactorAuthenticatable')
        ->removeTrait('HasFactory')
        ->removeInterface('MustVerifyEmail');

    unset($file);

    $contents = file_get_contents($path);

    expect($contents)
        ->toContain('use Illuminate\Database\Eloquent\Model;')
        ->toContain('use HasApiTokens;')
        ->not->toContain('implements MustVerifyEmail')
        ->not->toContain('use TwoFactorAuthenticatable,')
        ->not->toContain('    use HasFactory;');
});

it('can save a php file with no queued edits', function (): void {
    $path = $this->tempDir.'/SampleClass.php';

    copy(dirname(__DIR__).'/fixtures/php/SampleClass.php.stub', $path);

    $original = file_get_contents($path);

    (new Source($path))->save();

    expect(file_get_contents($path))->toBe($original);
});
