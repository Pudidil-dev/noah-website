<?php
declare(strict_types=1);

final class FanSubmissionTest extends DbTestCase
{
    private function input(string $type = 'story'): array
    {
        return [
            'name' => 'Sahabat Uji',
            'email' => 'sahabat@example.invalid',
            'type' => $type,
            'content' => 'Cerita dari komunitas.',
        ];
    }

    public function testStoryCanBeSubmittedWithoutFileOrCommunity(): void
    {
        $result = submit_fan(self::$pdo, $this->input());

        $this->assertTrue($result['ok']);
        $row = fetch_one(self::$pdo, 'SELECT community_id, file_url, status FROM fan_submissions WHERE email = ?', ['sahabat@example.invalid']);
        $this->assertSame(['community_id' => null, 'file_url' => null, 'status' => 'pending'], $row);
    }

    public function testSelectedCommunityIsPersisted(): void
    {
        $communityId = $this->insert("INSERT INTO communities (name, city) VALUES ('Sahabat Uji', 'Bogor')");
        $result = submit_fan(self::$pdo, $this->input() + ['community_id' => (string)$communityId]);

        $this->assertTrue($result['ok']);
        $this->assertSame($communityId, (int)fetch_one(self::$pdo,
            'SELECT community_id FROM fan_submissions WHERE email = ?', ['sahabat@example.invalid'])['community_id']);
    }

    public function testUnknownCommunityDoesNotCreateSubmission(): void
    {
        $result = submit_fan(self::$pdo, $this->input() + ['community_id' => '999999']);

        $this->assertFalse($result['ok']);
        $this->assertArrayHasKey('community_id', $result['errors']);
        $this->assertSame(0, (int)self::$pdo->query('SELECT count(*) FROM fan_submissions')->fetchColumn());
    }

    public function testArtworkAndCoverRequireFiles(): void
    {
        foreach (['artwork', 'cover'] as $type) {
            $result = submit_fan(self::$pdo, $this->input($type));
            $this->assertFalse($result['ok']);
            $this->assertArrayHasKey('file_url', $result['errors']);
        }
        $this->assertSame(0, (int)self::$pdo->query('SELECT count(*) FROM fan_submissions')->fetchColumn());
    }

    public function testInvalidUploadIsRejectedWithoutSavingSubmission(): void
    {
        $result = submit_fan(self::$pdo, $this->input('artwork'), ['error' => UPLOAD_ERR_INI_SIZE]);

        $this->assertFalse($result['ok']);
        $this->assertArrayHasKey('file_url', $result['errors']);
        $this->assertSame(0, (int)self::$pdo->query('SELECT count(*) FROM fan_submissions')->fetchColumn());
    }
}
