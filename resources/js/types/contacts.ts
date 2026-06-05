export type ContactAddressBook = {
    id: number;
    display_name: string;
    description: string | null;
    cards_count: number;
};

export type ContactLabeledValue = {
    label: string | null;
    value: string;
    types: string[];
    isPreferred: boolean;
    group: string | null;
};

export type ContactPostalAddress = {
    label: string | null;
    poBox: string | null;
    extended: string | null;
    street: string | null;
    city: string | null;
    region: string | null;
    postalCode: string | null;
    country: string | null;
    countryCode: string | null;
    types: string[];
    isPreferred: boolean;
    group: string | null;
};

export type ContactDate = {
    label: string | null;
    year: number | null;
    month: number | null;
    day: number | null;
    calendar: string | null;
    rawValue: string | null;
    group: string | null;
};

/** Mirrors Bambamboole\LaravelDav\Dto\ContactData (the `raw` field is stripped server-side). */
export type ContactData = {
    uid: string | null;
    formattedName: string | null;
    givenName: string | null;
    familyName: string | null;
    organization: string | null;
    contactType: string;
    birthday: ContactDate | null;
    emailAddresses: ContactLabeledValue[];
    phoneNumbers: ContactLabeledValue[];
    addresses: ContactPostalAddress[];
    namePrefix: string | null;
    middleName: string | null;
    nameSuffix: string | null;
    nickname: string | null;
    jobTitle: string | null;
    department: string | null;
    note: string | null;
};

export type Contact = {
    id: number;
    dav_address_book_id: number;
    uri: string;
    etag: string;
    last_modified_at: string;
    data: ContactData;
    address_book: {
        id: number;
        display_name: string;
    };
};
