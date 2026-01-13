<?php

namespace App\Http\Resources;

use App\Http\Resources\Collections\PermissionsCollection;
use App\Http\Resources\Collections\RolesCollection;
use App\Services\NotificationService;
use Illuminate\Http\Resources\Json\JsonResource;

class UsersResource extends JsonResource
{
    /**
     * Transform the resource into an array.
     *
     * @return array<string, mixed>
     */
    public function toArray($request): array
    {
        $dataNotifications = $this->getDataNotification();
        $preferred_language = $this->getPreferredLanguageUser();

        $data = [
            'id' => $this->id,
            'slug' => $this->slug,
            'first_name' => $this->first_name,
            'last_name' => $this->last_name,
            'roles' => $this->getRoles(),
            'permissions' => $this->getAllPermissions(),
            'email' => $this->email,
            'email_verified_at' => (bool) $this->email_verified_at,
            'confirmed' => (bool) $this->confirmed,
            'is_blocked' => (bool) $this->is_blocked,
            'otp_enabled' => (bool) $this->otp_enabled,
            'photo' => $this->photo,
            'birthday' => $this->birthday?->format(__('dateformat.datekey')),
            'phone' => $this->phone,
            'preferred_language' => $preferred_language,
            'languages' => $this->getAllLanguagesFormatted(),
            'available_countries' => CountryResource::collection($this->getCountriesWithUsers()),
            'unreadnotificationcount' => $dataNotifications['unreadnotificationcount'],
            'recent_unread_list' => $dataNotifications['recent_unread_list'],
            'session_user_second' => $this->session_user_second,
        ];
        // Si c'est une requête GET et que l'URL contient un ID (show)
        if ($request->isMethod('get') && $request->route('user')) {

            return [
                'message' => __('user.show'),
                'data' => $data,
            ];
        }
        // Si c'est POST, PATCH ou DELETE
        elseif ($request->isMethod('post') || $request->isMethod('put') || $request->isMethod('patch') || $request->isMethod('delete')) {
            if (strpos($request->getPathInfo(), 'search') === false) {
                $messageContent = $this->successMessage($request);

                // Exclure les actions de blocage/déblocage des notifications automatiques
                $isBlockAction = strpos($request->getPathInfo(), '/block') !== false ||
                    strpos($request->getPathInfo(), '/unblock') !== false ||
                    strpos($request->getPathInfo(), '/toggle-block') !== false;

                if (! $isBlockAction) {
                    NotificationService::sendNewNotification($messageContent);
                }
                activity()->event($request->method())->causedBy($this->clone)->log($messageContent);

                return [
                    'message' => $this->successMessage($request),
                    'data' => $data,
                ];
            }
        }

        // Par défaut (GET index, search, etc.)
        return $data;
    }

    private function successMessage($request): string
    {
        switch ($request->method()) {
            case 'POST':
                return __('user.created');
            case 'PATCH':
                return __('user.updated');
            case 'PUT':
                return __('user.updated');
            case 'DELETE':
                return __('user.deleted');
            default:
                return '';
        }
    }

    public function getRoles()
    {
        $originalResponse = new RolesCollection($this->roles);

        $responseArray = $originalResponse->toArray(request());

        return $responseArray['data'];
    }

    public function getAllPermissions()
    {

        $originalResponse = new PermissionsCollection($this->permissions);

        // Extraire les données de la collection et les restructurer
        $responseArray = $originalResponse->toArray(request());

        return $responseArray['data'];
    }

    public function getDataNotification(): array
    {
        $unreadnotificationcount = $this->unreadNotifications()->count();
        // 1. Les 5 dernières notifications non lues de l'utilisateur
        $recentUnreadNotifications = $this->unreadNotifications()
            ->latest() // Raccourci pour orderBy('created_at', 'desc')
            ->take(5)
            ->get();
        $formattedRecentUnread = NotificationResource::collection($recentUnreadNotifications)->resolve();
        $data = [
            'unreadnotificationcount' => $unreadnotificationcount, // Le nouveau total
            'recent_unread_list' => $formattedRecentUnread,
        ];

        return $data;
    }
}
