import type { App } from '@/wayfinder/types';

type ContactsIndexProps =
    App.Http.Controllers.Contacts.ContactController.Show.Response;

export type ContactAddressBook = ContactsIndexProps['addressBooks'][number];

export type Contact = ContactsIndexProps['contacts'][number];

export type ContactData = Contact['data'];

export type ContactDate = NonNullable<ContactData['birthday']>;

export type ContactLabeledValue = ContactData['emailAddresses'][number];

export type ContactPostalAddress = ContactData['addresses'][number];
