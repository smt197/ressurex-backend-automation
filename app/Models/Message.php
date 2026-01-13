<?php

namespace App\Models;

use Illuminate\Database\Eloquent\Casts\Attribute;
use Illuminate\Database\Eloquent\Concerns\HasUuids; // Pour Laravel 9+
use Illuminate\Database\Eloquent\Factories\HasFactory;
use Illuminate\Database\Eloquent\Model;
use Illuminate\Support\Facades\Storage;

class Message extends Model
{
    use HasFactory, HasUuids; // Pour Laravel 9+

    protected $fillable = [
        'conversation_id',
        'sender_id',
        'message', // Peut être null si c'est un fichier/image
        'read_at',
        'file_path',
        'file_name',
        'file_mime_type',
        'file_size',
        'message_type',
    ];

    protected $casts = [
        'read_at' => 'datetime',
        'file_size' => 'integer',
        'message_type' => 'string',
    ];

    protected function fileUrl(): Attribute
    {
        return Attribute::make(
            get: fn ($value, $attributes) => $attributes['file_path']
                ? Storage::disk('chat_attachments')->url($attributes['file_path']) // Utilisez le nom de votre disque
                : null,
        );
    }

    protected $appends = ['file_url'];

    public function conversation()
    {
        return $this->belongsTo(Conversation::class);
    }

    public function sender()
    {
        return $this->belongsTo(User::class, 'sender_id');
    }
}
