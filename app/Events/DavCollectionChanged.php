<?php

namespace App\Events;

use Illuminate\Broadcasting\InteractsWithSockets;
use Illuminate\Broadcasting\PrivateChannel;
use Illuminate\Contracts\Broadcasting\ShouldBroadcast;
use Illuminate\Contracts\Events\ShouldDispatchAfterCommit;
use Illuminate\Foundation\Events\Dispatchable;

class DavCollectionChanged implements ShouldBroadcast, ShouldDispatchAfterCommit
{
    use Dispatchable, InteractsWithSockets;

    public function __construct(
        public int $userId,
        public string $collectionType,
        public int $collectionId,
        public ?string $resourceUri,
        public string $operation,
        public int $syncToken,
    ) {}

    public function broadcastOn(): PrivateChannel
    {
        return new PrivateChannel('dav.'.$this->userId);
    }

    public function broadcastAs(): string
    {
        return 'dav.changed';
    }

    /**
     * @return array<string, mixed>
     */
    public function broadcastWith(): array
    {
        return [
            'type' => $this->collectionType,
            'collection_id' => $this->collectionId,
            'uri' => $this->resourceUri,
            'operation' => $this->operation,
            'sync_token' => $this->syncToken,
        ];
    }
}
