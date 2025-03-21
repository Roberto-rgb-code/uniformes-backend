<?php

namespace App\Models;

use Illuminate\Database\Eloquent\Factories\HasFactory;
use Illuminate\Database\Eloquent\Model;

class Foto extends Model {
  use HasFactory;

  protected $fillable = ['uniforme_id', 'foto_path'];

  public function uniforme() {
    return $this->belongsTo(Uniforme::class);
  }
}