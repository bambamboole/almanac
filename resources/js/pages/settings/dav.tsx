import { Form, Head, usePage } from '@inertiajs/react';
import { CalendarDays, KeyRound, Trash2 } from 'lucide-react';
import type { Inertia } from '@/wayfinder/types';
import DavCredentialController from '@/wayfinder/App/Http/Controllers/Settings/DavCredentialController';
import Heading from '@/components/heading';
import InputError from '@/components/input-error';
import { Alert, AlertDescription, AlertTitle } from '@/components/ui/alert';
import { Badge } from '@/components/ui/badge';
import { Button } from '@/components/ui/button';
import { Input } from '@/components/ui/input';
import { Label } from '@/components/ui/label';
import { edit } from '@/wayfinder/routes/settings/dav';

type CreatedDavCredential = {
    username: string;
    plainSecret: string;
};

type Props = Pick<Inertia.Pages.Settings.Dav, 'credentials' | 'davUrl'>;

export default function Dav({ credentials, davUrl }: Props) {
    const { flash } = usePage();
    const createdDavCredential = flash.createdDavCredential as
        | CreatedDavCredential
        | undefined;

    return (
        <>
            <Head title="DAV settings" />

            <h1 className="sr-only">DAV settings</h1>

            <div className="space-y-6">
                <Heading
                    variant="small"
                    title="DAV server"
                    description="Use this URL with calendar and contacts clients"
                />

                <div className="grid gap-2">
                    <Label htmlFor="dav_url">Server URL</Label>
                    <Input
                        id="dav_url"
                        readOnly
                        value={davUrl}
                        className="mt-1 block w-full font-mono text-sm"
                    />
                </div>
            </div>

            <div className="space-y-6">
                <Heading
                    variant="small"
                    title="Create credential"
                    description="Generate a separate password for each DAV client"
                />

                {createdDavCredential && (
                    <Alert>
                        <KeyRound className="h-4 w-4" />
                        <AlertTitle>Credential created</AlertTitle>
                        <AlertDescription>
                            <p>
                                Copy this password now. It will only be shown
                                once.
                            </p>
                            <dl className="mt-3 grid gap-3">
                                <div className="grid gap-1">
                                    <dt className="text-xs font-medium text-muted-foreground">
                                        Username
                                    </dt>
                                    <dd className="rounded-md bg-muted px-3 py-2 font-mono text-sm break-all text-foreground">
                                        {createdDavCredential.username}
                                    </dd>
                                </div>
                                <div className="grid gap-1">
                                    <dt className="text-xs font-medium text-muted-foreground">
                                        Password
                                    </dt>
                                    <dd className="rounded-md bg-muted px-3 py-2 font-mono text-sm break-all text-foreground">
                                        {createdDavCredential.plainSecret}
                                    </dd>
                                </div>
                            </dl>
                        </AlertDescription>
                    </Alert>
                )}

                <Form
                    {...DavCredentialController.store.form()}
                    options={{
                        preserveScroll: true,
                    }}
                    resetOnSuccess
                    className="space-y-6"
                >
                    {({ errors, processing }) => (
                        <>
                            <div className="grid gap-2">
                                <Label htmlFor="name">Name</Label>
                                <Input
                                    id="name"
                                    name="name"
                                    className="mt-1 block w-full"
                                    required
                                    maxLength={80}
                                    autoComplete="off"
                                    placeholder="Phone, laptop, calendar app"
                                />
                                <InputError message={errors.name} />
                            </div>

                            <div className="flex items-center gap-4">
                                <Button disabled={processing}>
                                    {processing
                                        ? 'Creating...'
                                        : 'Create credential'}
                                </Button>
                            </div>
                        </>
                    )}
                </Form>
            </div>

            <div className="space-y-6">
                <Heading
                    variant="small"
                    title="Credentials"
                    description="Revoke credentials that are no longer in use"
                />

                <div className="overflow-hidden rounded-lg border border-border">
                    {credentials.length > 0 ? (
                        credentials.map((credential) => (
                            <div
                                key={credential.id}
                                className="flex flex-col gap-4 border-b p-4 last:border-b-0 sm:flex-row sm:items-center sm:justify-between"
                            >
                                <div className="flex items-start gap-4">
                                    <div className="flex h-10 w-10 shrink-0 items-center justify-center rounded-xl bg-muted">
                                        <CalendarDays className="h-5 w-5 text-muted-foreground" />
                                    </div>
                                    <div className="min-w-0 space-y-1">
                                        <div className="flex flex-wrap items-center gap-2.5">
                                            <p className="font-medium tracking-tight">
                                                {credential.name}
                                            </p>
                                            <Badge
                                                variant="outline"
                                                className="font-mono"
                                            >
                                                {credential.username}
                                            </Badge>
                                        </div>
                                        <p className="text-sm text-muted-foreground">
                                            Added {credential.created_at_diff}
                                            {credential.last_used_at_diff && (
                                                <>
                                                    <span className="mx-1 text-muted-foreground/50">
                                                        /
                                                    </span>
                                                    Last used{' '}
                                                    {
                                                        credential.last_used_at_diff
                                                    }
                                                </>
                                            )}
                                        </p>
                                    </div>
                                </div>

                                <Form
                                    {...DavCredentialController.destroy.form(
                                        credential.id,
                                    )}
                                    options={{
                                        preserveScroll: true,
                                    }}
                                >
                                    {({ processing }) => (
                                        <Button
                                            variant="destructive"
                                            size="sm"
                                            disabled={processing}
                                        >
                                            <Trash2 className="h-4 w-4" />
                                            {processing
                                                ? 'Revoking...'
                                                : 'Revoke'}
                                        </Button>
                                    )}
                                </Form>
                            </div>
                        ))
                    ) : (
                        <div className="p-8 text-center">
                            <div className="mx-auto mb-4 flex h-14 w-14 items-center justify-center rounded-2xl bg-muted">
                                <CalendarDays className="h-7 w-7 text-muted-foreground" />
                            </div>
                            <p className="font-medium">No DAV credentials</p>
                            <p className="mt-1 text-sm text-muted-foreground">
                                Create one to connect a calendar or contacts
                                client.
                            </p>
                        </div>
                    )}
                </div>
            </div>
        </>
    );
}

Dav.layout = {
    breadcrumbs: [
        {
            title: 'DAV settings',
            href: edit(),
        },
    ],
};
