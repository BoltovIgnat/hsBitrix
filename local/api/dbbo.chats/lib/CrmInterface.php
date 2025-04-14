<?php

namespace Dbbo\Chat;

interface CrmInterface
{
    public function setAssignedBy(int $userId): void;

    public function setAssignedByEmail(string $email): void;
    /**
     * Имя пользователя по ID
     *
     * @param int $userId
     * @return string|null
     */
    public function getUserNameByID(int $userId): ?string;

    public function findDeal(array $dealFilter, bool $onlyOpen = true): array;

    public function updateDealChat(array $deal, array $chatData): bool;

    public function updateDealContact(array $deal, array $contactData): array;

    public function findLead(array $leadFilter, bool $onlyOpen = true): array;

    public function findLeadByContactData(array $contactData): array;

    public function updateLeadChat(array $lead, array $chatData): bool;

    public function updateLeadContact(array $lead, array $contactData): array;

    public function createLead(array $leadData, array $chat, ?int $contactId = null, ?int $companyId = null): ?array;

    public function findContact(array $contactData): array;

    public function findLeadByContact(int $contactId): array;

    public function bindLead(array $lead, array $leadFilter): ?array;

    public function bindDeal(array $deal, array $dealFilter): ?array;

    public function findDealByContact(int $contactId): array;

    public function getContactCompanies(int $contactId): array;

    public function getCompanyData(int $companyId): array;
}