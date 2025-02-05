<?php


namespace Hengebytes\ResponseFilterBundle\Command;

use Doctrine\ORM\EntityManagerInterface;
use Hengebytes\ResponseFilterBundle\Entity\ResponseFilterRule;
use Hengebytes\ResponseFilterBundle\Enum\ResponseFilterRuleTypeEnum;
use Symfony\Component\Console\Attribute\AsCommand;
use Symfony\Component\Console\Command\Command;
use Symfony\Component\Console\Helper\TableSeparator;
use Symfony\Component\Console\Input\InputInterface;
use Symfony\Component\Console\Output\OutputInterface;
use Symfony\Component\Console\Style\SymfonyStyle;
use Symfony\Component\Validator\Validator\ValidatorInterface;

#[AsCommand(name: 'hb_response_filter:manage', description: 'Manage filters')]
class FiltersManageCommand extends Command
{
    public function __construct(
        private readonly EntityManagerInterface $em,
        private readonly ValidatorInterface $validator
    ) {
        parent::__construct();
    }

    protected function execute(InputInterface $input, OutputInterface $output): int
    {
        $io = new SymfonyStyle($input, $output);
        $io->title('Start manage filters');
        $io->writeln('Please select an action to perform');

        while (true) {
            $action = $io->choice('Select action', ['Help', 'Create', 'Update', 'Delete', 'List', 'Copy', 'Exit']);
            if ($action === 'Exit') {
                break;
            }
            $this->performAction($action, $io);
        }

        return Command::SUCCESS;
    }

    private function performAction(string $action, SymfonyStyle $io): void
    {
        match ($action) {
            'Help' => $this->help($io),
            'Create' => $this->createFilter($io),
            'Update' => $this->updateFilter($io),
            'Delete' => $this->deleteFilter($io),
            'Copy' => $this->copyFilter($io),
            'List' => $this->listFilters($io),
            default => throw new \InvalidArgumentException('Invalid action'),
        };
    }

    private function listFilters(SymfonyStyle $io): void
    {
        $filters = $this->em->getRepository(ResponseFilterRule::class)->findAll();
        $rows = [];
        foreach ($filters as $filter) {
            $rows[] = [
                '<fg=green>' . $filter->id . '</>',
                $filter->service . ($filter->subService ? ' - ' . $filter->subService : ''),
                $filter->action,
                $filter->type->name,
                $filter->condition,
                $filter->field,
                $filter->value,
            ];
            $rows[] = new TableSeparator();
        }
        $table = $io->createTable();
        $table->setStyle('box');
        $table->setColumnMaxWidth(4, 40);
        $table->setColumnMaxWidth(5, 40);
        $table->setColumnMaxWidth(6, 40);
        $table
            ->setHeaders(['ID', 'Service', 'Action', 'Type', 'Condition', 'Field', 'Value'])
            ->setRows($rows);
        $table->render();
    }

    private function help(SymfonyStyle $io): void
    {
        $io->title('Help');
        $io->info('Condition:');
        $io->writeln('key/../key/fieldName(= /< />)fieldValue');
        $io->writeln('key/../key/fieldName=["fieldValue1", "fieldValue1", ...]');

        $io->info('Field:');
        $io->writeln('key/../key/fieldName');
        $io->writeln('fieldName');
        $io->writeln('Start next after * in condition');
        $io->writeln('!* means iterate over all fields but not apply filter');

        $io->info('Value:');
        $io->writeln('{"from":"to", "from1":"to1", ...}');
        $io->writeln('from=to');
        $io->writeln('from=>to');
    }

    private function createFilter(SymfonyStyle $io): void
    {
        $io->title('Create filter');
        $service = $io->ask('Service');
        $subService = $io->ask('Sub service');
        $action = $io->ask('Action');

        $selectedType = $io->choice('Type', array_map(static fn($type) => $type->name, ResponseFilterRuleTypeEnum::cases()));
        if ($selectedType === ResponseFilterRuleTypeEnum::REMOVE->name) {
            $type = ResponseFilterRuleTypeEnum::REMOVE;
        } elseif ($selectedType === ResponseFilterRuleTypeEnum::STR_REPLACE->name) {
            $type = ResponseFilterRuleTypeEnum::STR_REPLACE;
        } elseif ($selectedType === ResponseFilterRuleTypeEnum::SET->name) {
            $type = ResponseFilterRuleTypeEnum::SET;
        } else {
            $io->error('Invalid type');

            return;
        }
        $condition = $io->ask('Condition');
        $field = $io->ask('Field');
        $value = $io->ask('Value');

        $filter = new ResponseFilterRule();
        $filter->service = $service;
        $filter->subService = $subService;
        $filter->action = $action;
        $filter->type = $type;
        $filter->condition = $condition;
        $filter->field = $field;
        $filter->value = $value;

        $errors = $this->validator->validate($filter);

        if (count($errors) > 0) {
            $io->error((string)$errors);

            return;
        }

        $this->em->persist($filter);
        $this->em->flush();

        $io->success('Filter created');
    }

    private function deleteFilter(SymfonyStyle $io): void
    {
        $io->title('Delete filter');
        $id = $io->ask('ID');
        if (!$id) {
            $io->error('ID is required');

            return;
        }
        $filter = $this->em->getRepository(ResponseFilterRule::class)->find($id);
        if (!$filter) {
            $io->error('Filter not found');

            return;
        }

        $this->em->remove($filter);
        $this->em->flush();

        $io->success('Filter deleted');
    }

    private function updateFilter(SymfonyStyle $io): void
    {
        $io->title('Update filter');
        $id = $io->ask('ID');
        if (!$id) {
            $io->error('ID is required');

            return;
        }
        $filter = $this->em->getRepository(ResponseFilterRule::class)->find($id);
        if (!$filter) {
            $io->error('Filter not found');

            return;
        }

        $service = $io->ask('Service', $filter->service);
        $subService = $io->ask('Sub service', $filter->subService);
        $action = $io->ask('Action', $filter->action);

        $selectedType = $io->choice('Type', array_map(static fn($type) => $type->name, ResponseFilterRuleTypeEnum::cases()), $filter->type->name);
        if ($selectedType === ResponseFilterRuleTypeEnum::REMOVE->name) {
            $type = ResponseFilterRuleTypeEnum::REMOVE;
        } elseif ($selectedType === ResponseFilterRuleTypeEnum::STR_REPLACE->name) {
            $type = ResponseFilterRuleTypeEnum::STR_REPLACE;
        } elseif ($selectedType === ResponseFilterRuleTypeEnum::SET->name) {
            $type = ResponseFilterRuleTypeEnum::SET;
        } else {
            $io->error('Invalid type');

            return;
        }
        $condition = $io->ask('Condition', $filter->condition);
        $field = $io->ask('Field', $filter->field);
        $value = $io->ask('Value', $filter->value);

        $filter->service = $service;
        $filter->subService = $subService;
        $filter->action = $action;
        $filter->type = $type;
        $filter->condition = $condition;
        $filter->field = $field;
        $filter->value = $value;

        $errors = $this->validator->validate($filter);

        if (count($errors) > 0) {
            $io->error((string)$errors);

            return;
        }

        $this->em->flush();

        $io->success('Filter updated');
    }

    private function copyFilter(SymfonyStyle $io): void
    {
        $io->title('Copy filter');
        $id = $io->ask('ID');
        if (!$id) {
            $io->error('ID is required');

            return;
        }
        $existingFilter = $this->em->getRepository(ResponseFilterRule::class)->find($id);
        if (!$existingFilter) {
            $io->error('Filter not found');

            return;
        }

        $filter = clone $existingFilter;
        $filter->id = null;

        $service = $io->ask('Service', $filter->service);
        $subService = $io->ask('Sub service', $filter->subService);
        $action = $io->ask('Action', $filter->action);

        $selectedType = $io->choice(
            'Type', array_map(static fn($type) => $type->name, ResponseFilterRuleTypeEnum::cases()),
            $filter->type->name
        );
        if ($selectedType === ResponseFilterRuleTypeEnum::REMOVE->name) {
            $type = ResponseFilterRuleTypeEnum::REMOVE;
        } elseif ($selectedType === ResponseFilterRuleTypeEnum::STR_REPLACE->name) {
            $type = ResponseFilterRuleTypeEnum::STR_REPLACE;
        } elseif ($selectedType === ResponseFilterRuleTypeEnum::SET->name) {
            $type = ResponseFilterRuleTypeEnum::SET;
        } else {
            $io->error('Invalid type');

            return;
        }
        $condition = $io->ask('Condition', $filter->condition);
        $field = $io->ask('Field', $filter->field);
        $value = $io->ask('Value', $filter->value);

        $filter->service = $service;
        $filter->subService = $subService;
        $filter->action = $action;
        $filter->type = $type;
        $filter->condition = $condition;
        $filter->field = $field;
        $filter->value = $value;

        $errors = $this->validator->validate($filter);

        if (count($errors) > 0) {
            $io->error((string)$errors);

            return;
        }

        $this->em->persist($filter);
        $this->em->flush();

        $io->success('Filter copied');
    }
}