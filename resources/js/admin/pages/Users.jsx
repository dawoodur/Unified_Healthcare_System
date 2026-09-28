import React, { useEffect, useState } from 'react';
import { Link, useSearchParams } from 'react-router-dom';
import client from '../api/client';
import PageShell, { Card, EmptyState } from '../components/PageShell';
import { YesNo } from '../components/Widgets';

/**
 * One looked-up account. The per-role field list comes from the API, which
 * mirrors the switch the Blade page used, so nothing an admin could see
 * before has gone missing.
 */
function AccountRecord({ account }) {
  return (
    <>
      <Card
        icon="bi-person-vcard"
        tone="is-indigo"
        title={account.name || account.uid_tag}
        subtitle={`${account.uid_tag} · ${account.role.charAt(0).toUpperCase()}${account.role.slice(1)}`}
      >
        <dl className="admin-kv">
          <dt>Email</dt><dd>{account.email}</dd>
          <dt>Mobile</dt><dd>{account.mobile || '—'}</dd>
          <dt>Verified</dt><dd><YesNo value={account.is_verified} /></dd>
          <dt>Active</dt><dd><YesNo value={account.is_active} /></dd>
          <dt>Joined</dt><dd>{account.joined_label}</dd>
          <dt>Last login</dt><dd>{account.last_login_label || 'Never'}</dd>
        </dl>
      </Card>

      <Card icon="bi-card-list" tone="is-teal" title="Profile details" subtitle="The role-specific record behind this account.">
        {account.note && <p className="admin-notice is-info">{account.note}</p>}

        {account.fields.length > 0 && (
          <dl className="admin-kv">
            {account.fields.map((field) => (
              <React.Fragment key={field.label}>
                <dt>{field.label}</dt>
                <dd>
                  {field.badge
                    ? <span className={`admin-badge is-${field.badge}`}>{field.value}</span>
                    : (field.value ?? '—')}
                </dd>
              </React.Fragment>
            ))}
          </dl>
        )}

        {account.counts.length > 0 && (
          <div className="admin-counts" style={{ marginTop: '14px' }}>
            {account.counts.map((count) => (
              <div key={count.label}>
                <strong>{count.value}</strong>
                <small>{count.label}</small>
              </div>
            ))}
          </div>
        )}
      </Card>
    </>
  );
}

export default function Users() {
  const [params, setParams] = useSearchParams();
  const [data, setData] = useState(null);
  const [error, setError] = useState(null);

  const uid = params.get('u_id') || '';
  const name = params.get('name') || '';

  // Kept separate from the URL so typing does not fire a request per keystroke
  // — the search only runs when the form is submitted.
  const [uidInput, setUidInput] = useState(uid);
  const [nameInput, setNameInput] = useState(name);

  useEffect(() => {
    setUidInput(uid);
    setNameInput(name);
  }, [uid, name]);

  function load() {
    setError(null);
    setData(null);
    client
      .get('/users', { params: { u_id: uid || undefined, name: name || undefined } })
      .then((res) => setData(res.data))
      .catch(() => setError('Could not load the user list right now.'));
  }

  useEffect(load, [uid, name]);

  function submit(event) {
    event.preventDefault();
    const next = {};
    if (uidInput.trim()) next.u_id = uidInput.trim();
    if (nameInput.trim()) next.name = nameInput.trim();
    setParams(next);
  }

  return (
    <PageShell
      pageClass="admin-users-page"
      icon="bi-search"
      title="Search users"
      subtitle="Every user — any of the six roles — has a permanent u_id assigned the moment their account is created. Look one up for their full record, or filter the list by name."
      loading={!data}
      error={error}
      onRetry={load}
      loadingTitle="Loading users"
      loadingMessage="Fetching accounts…"
    >
      {data && (
        <>
          <Card icon="bi-funnel" tone="is-blue" title="Look someone up" subtitle="By u_id for one full record, by name to narrow the list.">
            <form className="admin-form-row" onSubmit={submit}>
              <div className="admin-field" style={{ flex: '0 1 160px' }}>
                <label htmlFor="admin-uid">u_id</label>
                <input
                  id="admin-uid"
                  type="number"
                  min="1"
                  placeholder="e.g. 12"
                  value={uidInput}
                  onChange={(event) => setUidInput(event.target.value)}
                />
              </div>
              <div className="admin-field">
                <label htmlFor="admin-name">Name</label>
                <input
                  id="admin-name"
                  type="text"
                  placeholder="e.g. Rahim"
                  value={nameInput}
                  onChange={(event) => setNameInput(event.target.value)}
                />
              </div>
              <button type="submit" className="admin-btn is-primary">
                <i className="bi bi-search" aria-hidden="true"></i> Search
              </button>
              {(uid || name) && (
                <Link to="" className="admin-btn is-ghost">Clear</Link>
              )}
            </form>
          </Card>

          {data.not_found && (
            <p className="admin-notice is-error">
              <i className="bi bi-exclamation-circle" aria-hidden="true"></i>
              No user found with u_id #{data.query.u_id}.
            </p>
          )}

          {data.account && <AccountRecord account={data.account} />}

          <Card
            icon="bi-people"
            tone="is-violet"
            title={name ? `All users matching “${name}”` : 'All users'}
            subtitle={`${data.accounts.length} account${data.accounts.length === 1 ? '' : 's'}.`}
          >
            {data.accounts.length === 0 ? (
              <EmptyState icon="bi-person-x" title="No users match that name" message="Clear the filter to see everyone." />
            ) : (
              <div className="admin-table-wrap">
                <table className="admin-table">
                  <thead>
                    <tr>
                      <th>u_id</th><th>Name</th><th>Role</th><th>Email</th>
                      <th>Verified</th><th>Active</th><th>Joined</th>
                    </tr>
                  </thead>
                  <tbody>
                    {data.accounts.map((account) => (
                      <tr key={account.account_id}>
                        <td>
                          <Link to={`?u_id=${account.account_id}${name ? `&name=${encodeURIComponent(name)}` : ''}`}>
                            {account.uid_tag}
                          </Link>
                        </td>
                        <td>{account.name}</td>
                        <td><span className="admin-badge">{account.role}</span></td>
                        <td>{account.email}</td>
                        <td><YesNo value={account.is_verified} /></td>
                        <td><YesNo value={account.is_active} /></td>
                        <td>{account.joined_label}</td>
                      </tr>
                    ))}
                  </tbody>
                </table>
              </div>
            )}
          </Card>
        </>
      )}
    </PageShell>
  );
}
