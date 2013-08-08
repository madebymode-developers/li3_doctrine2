<?php
use lithium\core\Libraries;
use lithium\util\Validator;

$doctrine_lib_root = \lithium\core\Libraries::get('li3_doctrine2', 'path');
$doctrine_base_dir = $doctrine_lib_root . '/_source/doctrine2';

if (!$doctrine_lib_root) {
    throw new Exception('li3_doctrine2 required for media library');
}

require $doctrine_base_dir . '/lib/Doctrine/ORM/Tools/Setup.php';
\Doctrine\ORM\Tools\Setup::registerAutoloadGit($doctrine_base_dir);

\Doctrine\DBAL\Types\Type::addType('json', 'li3_media\models\types\JsonType');

$config += array(
	'validators' => false
);

if ($config['validators']) {
	Validator::add('unique', function($value, $format, $options) {
		$options += array(
			'conditions' => array(),
			'getEntityManager' => 'getEntityManager',
			'connection' => isset($options['model']::$connectionName) ?
				$options['model']::$connectionName :
				'default',
			'checkPrimaryKey' => true
		);

		$entityManager = null;
		if (
			!empty($options['getEntityManager']) &&
			method_exists($options['model'], $options['getEntityManager']) &&
			is_callable($options['model'] . '::' . $options['getEntityManager'])
		) {
			$entityManager = call_user_func($options['model'] . '::' . $options['getEntityManager']);
		} elseif (!empty($options['connection'])) {
			$entityManager = lithium\data\Connections::get($options['connection'])->getEntityManager();
		}

		if (!$entityManager) {
			throw new \lithium\core\ConfigException('Could not get the entity manager');
		}

		$conditions = array(
			$options['field'] => $value
		) + $options['conditions'];

		$query = $entityManager->createQueryBuilder();

		$expressions = array();
		$p = 1;
		foreach($conditions as $field => $value) {
			$expressions[] = $query->expr()->eq('m.'.$field, '?'.$p);
			$query->setParameter($p, $value);
			$p++;
		}

		if ($options['checkPrimaryKey'] && !empty($options['values'])) {
			$metaData = $entityManager->getClassMetadata($options['model']);
			foreach($metaData->identifier as $field) {
				if (isset($options['values'][$field])) {
					$expressions[] = $query->expr()->neq('m.'.$field, '?'.$p);
					$query->setParameter($p, $options['values'][$field]);
					$p++;
				}
			}
		}

		$query->add('select', 'count(m.' . $options['field'] . ') total')
			  ->add('from', $options['model'] . ' m')
			  ->add('where', call_user_func_array(array($query->expr(), 'andx'), $expressions));
		$result = $query->getQuery()->getSingleResult();
		return empty($result['total']);
	});
}

?>
