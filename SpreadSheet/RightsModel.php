<?php

namespace Ac\Pm\SpreadSheet;

/**
 * В классе содержатся лишь дефолтные поля. Использование и изменения можно посмотреть в \Ac\Pm\Spg\RightsModels\MainRightsModel и производных классах
 * Class RightsModel
 * @package Ac\Pm\SpreadSheet
 */
abstract class RightsModel
{
    protected int $userId;
    protected array $userRights;
    
    protected const RIGHT_TYPES = ['all'];
    
    public function __construct(int $userId = 0)
    {
        if (empty($userId)) {
            $userId = $GLOBALS['USER']->GetID();
        }
        $this->userId = $userId;
    }
    
    abstract public function getUserRights(): array;
    
    public function getReadonlyColumns(): array
    {
        return [];
    }
    
    public function getHiddenColumns(): array
    {
        return [];
    }
    
    public function getMainFilter(): array
    {
        return [];
    }

    public function canVisit(): bool
    {
        return !empty($this->getUserRights());
    }
    
    public function getCheckboxSendField(): ?string
    {
        return null;
    }
    
    public function setUserTableParams(array &$userTableParams): void
    {
    }
}