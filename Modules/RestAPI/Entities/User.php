<?php

namespace Modules\RestAPI\Entities;

use Illuminate\Database\Eloquent\Relations\HasMany;
use Illuminate\Support\Str;
use Laravel\Sanctum\HasApiTokens;
use Laravel\Sanctum\NewAccessToken;

class User extends \App\Models\User
{
    use HasApiTokens;

    protected $default = [
        'id',
        'name',
        'email',
        'status',
    ];

    protected $guarded = [
        'id',
        'created_at',
        'updated_at',
    ];

    protected $filterable = [
        'id',
        'users.name',
        'email',
        'status',
    ];

    protected $hidden = ['modules','employeeDetail','clientDetails'];

    public function createToken(string $name, array $abilities = ['*'], \DateTimeInterface $expiresAt = null, array $claims = []): NewAccessToken
    {
        $token = $this->tokens()->create([
            'name' => $name,
            'token' => hash('sha256', $plainTextToken = Str::random(40)),
            'abilities' => $abilities,
            'expires_at' => $expiresAt,
            'claims' => $claims,
        ]);

        return new NewAccessToken($token, $token->getKey() . '|' . $plainTextToken);
    }

    public static function getCacheKey($id)
    {
        return 'user_'.$id;
    }

    public function devices(): HasMany
    {
        return $this->hasMany(Device::class);
    }
    public function clientCoApplicant(): HasMany
    {
        return $this->HasMany(ClientCoApplicant::class, 'client_id');
    }
    public function clientProjects(): HasMany
    {
        return $this->HasMany(Project::class, 'client_id');
    }
    public function setPasswordAttribute($value)
    {
        if ($value) {
            $this->attributes['password'] = \Hash::make($value);
        }
    }


    public static function allActiveClients()
    {
        $clients = User::with('clientCoApplicant','clientProjects')->join('role_user', 'role_user.user_id', '=', 'users.id')
            ->join('client_details', 'client_details.user_id', '=', 'users.id')
            ->join('roles', 'roles.id', '=', 'role_user.role_id')
            ->select('users.id', 'users.name','users.email','client_details.shipping_address','client_details.postal_code','client_details.city','client_details.mobile','client_details.state','client_details.country_id','client_details.origanisation_number','client_details.vat_number')
            ->where('roles.name', 'client')->whereNull('users.deleted_at');

        return $clients->orderBy('users.created_at', 'asc')->get();
    }
}
