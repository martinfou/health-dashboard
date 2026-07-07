<?php
namespace App\Models;
use Illuminate\Database\Eloquent\Factories\HasFactory;
use Illuminate\Foundation\Auth\User as Authenticatable;
use Illuminate\Notifications\Notifiable;

class User extends Authenticatable
{
    use HasFactory, Notifiable;

    protected $fillable = [
        'name', 'email', 'password', 'role',
    ];

    protected $hidden = ['password', 'remember_token'];

    protected function casts(): array {
        return [
            'email_verified_at' => 'datetime',
            'password' => 'hashed',
        ];
    }

    public function weightReadings() { return $this->hasMany(WeightReading::class); }
    public function bodyMeasurements() { return $this->hasMany(BodyMeasurement::class); }
    public function nutritionLogs() { return $this->hasMany(NutritionLog::class); }
    public function activityLogs() { return $this->hasMany(ActivityLog::class); }
    public function healthReports() { return $this->hasMany(HealthReport::class); }
    public function healthInsights() { return $this->hasMany(HealthInsight::class); }

    public function isAdmin(): bool { return $this->role === 'admin'; }
    public function canShare(): bool { return in_array($this->role, ['admin', 'viewer']); }
}
