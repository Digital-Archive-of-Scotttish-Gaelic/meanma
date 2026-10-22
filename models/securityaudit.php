<?php

namespace models;

use PDO;

final class securityaudit
{
  public static function record(
    string $actor,
    ?string $target,
    string $action,
    string $outcome,
    array $details = [],
    ?PDO $connection = null
  ): void {
    foreach (array_keys($details) as $key) {
      if (preg_match('/password|hash|token|session|secret|salt/i', (string) $key)) {
        throw new \InvalidArgumentException('Sensitive audit detail key rejected');
      }
    }
    $connection = $connection ?? (new database())->getDatabaseHandle();
    $statement = $connection->prepare(
      'INSERT INTO security_audit(actor_email,target_email,action,outcome,details_json)
       VALUES(:actor,:target,:action,:outcome,:details)'
    );
    $statement->execute([
      ':actor' => $actor,
      ':target' => $target,
      ':action' => $action,
      ':outcome' => $outcome,
      ':details' => $details ? json_encode($details, JSON_THROW_ON_ERROR) : null,
    ]);
  }

  public static function recent(int $limit = 200): array {
    $limit = max(1, min(500, $limit));
    $db = new database();
    return $db->getDatabaseHandle()->query(
      'SELECT actor_email,target_email,action,outcome,details_json,created_at
       FROM security_audit ORDER BY id DESC LIMIT ' . $limit
    )->fetchAll(PDO::FETCH_ASSOC);
  }
}
