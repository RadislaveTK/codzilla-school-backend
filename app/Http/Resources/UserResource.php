<?php

namespace App\Http\Resources;

use Illuminate\Http\Request;
use Illuminate\Http\Resources\Json\JsonResource;

class UserResource extends JsonResource
{
    public function toArray(Request $request): array
    {
        return array_merge(parent::toArray($request), [
            'name' => $this->name,
            'email' => $this->email,
            'phone' => $this->phone,
            'role' => $this->role,

            // Вычисляемые поля
            'is_admin' => $this->role === 'admin',
            'is_parent' => $this->role === 'parent',
            'avatar' => $this->getAvatarUrl(), // если есть метод в модели

            // Загруженные связи (только если есть)
            'children' => $this->whenLoaded('children', fn () => StudentResource::collection($this->children)),
            'children_count' => $this->whenCounted('children'),

            // Скрываем email для не-админов (если нужно)
            'email_private' => $this->when($request->user()?->isAdmin(), $this->email),
        ]);
    }

    /**
     * Дополнительные данные для коллекции пользователей
     */
    public static function collection($resource)
    {
        $total = method_exists($resource, 'total') ? $resource->total() : $resource->count();

        return parent::collection($resource)->additional([
            'stats' => [
                'total' => $total,
                'admins' => $resource->where('role', 'admin')->count(),
                'parents' => $resource->where('role', 'parent')->count(),
            ]
        ]);
    }
}
