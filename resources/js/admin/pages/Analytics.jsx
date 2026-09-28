import React, { useEffect, useState } from 'react';
import client from '../api/client';
import PageShell, { Card } from '../components/PageShell';
import { BarChart, LineChart, DonutChart, RankList } from '../components/Widgets';

// revenue/registrations are a trend over six months, so a line reads
// correctly where a bar-per-month implies six unrelated totals. doctors/
// medicines rank named things whose labels are too long to sit under a
// column, so those get horizontal rows. orders is a status breakdown — a
// proportion of a whole, which reads best as a donut.
const LINE = ['revenue', 'registrations'];
const RANKED = ['doctors', 'medicines'];
const DONUT = ['orders'];

export default function Analytics() {
  const [data, setData] = useState(null);
  const [error, setError] = useState(null);

  function load() {
    setError(null);
    client
      .get('/analytics')
      .then((res) => setData(res.data))
      .catch(() => setError('Could not load analytics right now.'));
  }

  useEffect(load, []);

  return (
    <PageShell
      pageClass="admin-analytics-page"
      icon="bi-graph-up-arrow"
      title="Platform analytics"
      subtitle="Revenue and registrations over the last six months, with activity across the platform."
      loading={!data}
      error={error}
      onRetry={load}
      loadingTitle="Loading analytics"
      loadingMessage="Crunching six months of activity…"
    >
      {data && data.charts.map((chart) => (
        <Card key={chart.key} icon={chart.icon} tone={`is-${chart.tone}`} title={chart.title}>
          {RANKED.includes(chart.key) && <RankList rows={chart.rows} />}
          {LINE.includes(chart.key) && <LineChart rows={chart.rows} tone={chart.key === 'registrations' ? 'indigo' : 'green'} />}
          {DONUT.includes(chart.key) && <DonutChart rows={chart.rows} totalLabel={chart.title} />}
          {!RANKED.includes(chart.key) && !LINE.includes(chart.key) && !DONUT.includes(chart.key) && <BarChart rows={chart.rows} />}
        </Card>
      ))}
    </PageShell>
  );
}
