<?php

namespace App\Models;

use App\Mail\FamilyLoginLink;
use Database\Factories\FamilyFactory;
use Illuminate\Database\Eloquent\Factories\HasFactory;
use Illuminate\Database\Eloquent\Relations\HasMany;
use Illuminate\Foundation\Auth\User as Authenticatable;
use Illuminate\Notifications\Notifiable;
use Illuminate\Support\Carbon;
use Illuminate\Support\Facades\Mail;
use Illuminate\Support\Str;

use function Illuminate\Support\defer;

/**
 * A parent or guardian account for the family portal. There is no password: the
 * family signs in with a single-use link emailed to them (see issueLoginToken).
 *
 * @property int $id
 * @property string $name
 * @property string $email
 * @property string|null $phone
 * @property string|null $login_token
 * @property Carbon|null $login_token_expires_at
 * @property Carbon|null $last_login_at
 */
class Family extends Authenticatable
{
    /** @use HasFactory<FamilyFactory> */
    use HasFactory, Notifiable;

    public const int LINK_MINUTES = 60;

    protected $fillable = ['name', 'email', 'phone'];

    protected $hidden = ['login_token', 'remember_token'];

    #[\Override]
    protected function casts(): array
    {
        return ['login_token_expires_at' => 'datetime', 'last_login_at' => 'datetime'];
    }

    /**
     * Families have no password. The session guard still asks for one when it
     * writes a "remember me" cookie, so it gets a constant empty string.
     */
    #[\Override]
    public function getAuthPassword(): string
    {
        return '';
    }

    /** @return HasMany<Application, $this> */
    public function applications(): HasMany
    {
        return $this->hasMany(Application::class)->latest('id');
    }

    /**
     * A fresh sign-in token. Only its SHA-256 hash is stored, so a database leak
     * cannot be turned into a working link. Issuing a new one cancels the last.
     */
    public function issueLoginToken(): string
    {
        $plain = Str::random(48);
        $this->forceFill([
            'login_token' => hash('sha256', $plain),
            'login_token_expires_at' => now()->addMinutes(self::LINK_MINUTES),
        ])->save();

        return $plain;
    }

    public function loginUrl(): string
    {
        return route('family.login.consume', ['token' => $this->issueLoginToken()]);
    }

    /**
     * Issue a fresh link and email it. With $afterResponse the email goes out once the
     * response has been sent, so a sign-in request for a known address takes no longer
     * than one for an unknown address. A failed send is reported, never thrown.
     *
     * @return array{url: string, sent: bool}
     */
    public function sendLoginLink(bool $newApplication = false, bool $afterResponse = false): array
    {
        $url = $this->loginUrl();
        $send = function () use ($url, $newApplication): bool {
            try {
                Mail::to($this->email)->send(new FamilyLoginLink($this->name, $url, $newApplication));

                return true;
            } catch (\Throwable $e) {
                report($e);

                return false;
            }
        };

        if ($afterResponse) {
            defer(fn () => $send());

            return ['url' => $url, 'sent' => true];
        }

        return ['url' => $url, 'sent' => $send()];
    }

    /** The family a link belongs to, if the link is real and unexpired. */
    public static function findByLoginToken(string $plain): ?self
    {
        if (strlen($plain) !== 48) {
            return null;
        }

        return static::query()
            ->where('login_token', hash('sha256', $plain))
            ->where('login_token_expires_at', '>', now())
            ->first();
    }

    /** Links work once. */
    public function consumeLoginToken(): void
    {
        $this->forceFill(['login_token' => null, 'login_token_expires_at' => null, 'last_login_at' => now()])->save();
    }
}
