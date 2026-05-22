<?php

declare(strict_types=1);

namespace GlpiPlugin\Webauthn\Service;

use Webauthn\CredentialRecord;
use Webauthn\PublicKeyCredentialSource;

final class CredentialRepository
{
    public function saveCredentialRecord(CredentialRecord $record, int $users_id, string $name): void
    {
        global $DB;

        $credentialId = $record->publicKeyCredentialId;
        $existing     = \PluginWebauthnCredential::findByCredentialId($credentialId);

        $row = [
            'users_id'              => $users_id,
            'name'                  => $name,
            'credential_id'         => $credentialId,
            'credential_public_key' => base64_encode($record->credentialPublicKey),
            'type'                  => $record->type,
            'attestation_type'      => $record->attestationType,
            'trust_path'            => $record->trustPath !== null ? serialize($record->trustPath) : null,
            'aaguid'                => $record->aaguid->toRfc4122(),
            'transports'            => json_encode($record->transports ?? []),
            'sign_count'            => $record->counter,
            'user_handle'           => $record->userHandle,
            'is_active'             => 1,
            'date_mod'              => date('Y-m-d H:i:s'),
        ];

        if ($existing !== null) {
            $DB->update(\PluginWebauthnCredential::getTable(), $row, ['id' => $existing['id']]);
            return;
        }

        $row['date_creation'] = date('Y-m-d H:i:s');
        $DB->insert(\PluginWebauthnCredential::getTable(), $row);
    }

    public function findOneByCredentialId(string $publicKeyCredentialId): ?CredentialRecord
    {
        $row = \PluginWebauthnCredential::findByCredentialId($publicKeyCredentialId);
        if ($row === null) {
            return null;
        }

        return $this->rowToRecord($row);
    }

    /**
     * @return CredentialRecord[]
     */
    public function findAllForUserEntity(string $userHandle): array
    {
        global $DB;

        $records = [];
        foreach (
            $DB->request([
                'FROM'  => \PluginWebauthnCredential::getTable(),
                'WHERE' => [
                    'user_handle' => $userHandle,
                    'is_active'   => 1,
                ],
            ]) as $row
        ) {
            $records[] = $this->rowToRecord($row);
        }

        return $records;
    }

    public function updateCounter(CredentialRecord $record, int $dbId): void
    {
        global $DB;

        $DB->update(
            \PluginWebauthnCredential::getTable(),
            [
                'sign_count'   => $record->counter,
                'last_used_at' => date('Y-m-d H:i:s'),
                'date_mod'     => date('Y-m-d H:i:s'),
            ],
            ['id' => $dbId]
        );
    }

    public function getDbIdByCredentialId(string $credentialId): ?int
    {
        $row = \PluginWebauthnCredential::findByCredentialId($credentialId);
        return $row !== null ? (int) $row['id'] : null;
    }

    private function rowToRecord(array $row): CredentialRecord
    {
        $trustPath = null;
        if (!empty($row['trust_path'])) {
            $trustPath = @unserialize($row['trust_path'], ['allowed_classes' => true]);
        }

        $transports = [];
        if (!empty($row['transports'])) {
            $decoded = json_decode($row['transports'], true);
            if (is_array($decoded)) {
                $transports = $decoded;
            }
        }

        $aaguidStr = $row['aaguid'] ?? '00000000-0000-0000-0000-000000000000';
        try {
            $aaguid = \Symfony\Component\Uid\Uuid::fromString($aaguidStr);
        } catch (\Throwable) {
            $aaguid = \Symfony\Component\Uid\Uuid::fromString('00000000-0000-0000-0000-000000000000');
        }

        $record = PublicKeyCredentialSource::create(
            $row['credential_id'],
            $row['type'] ?? 'public-key',
            $transports,
            $row['attestation_type'] ?? 'none',
            $trustPath,
            $aaguid,
            base64_decode($row['credential_public_key'], true) ?: '',
            $row['user_handle'],
            (int) $row['sign_count'],
        );

        if (property_exists($record, 'backupEligible')) {
            $record->backupEligible = false;
        }

        return $record;
    }
}
