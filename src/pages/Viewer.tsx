import { useEffect, useState } from 'react';
import { supabase } from '../lib/supabase';
import { Offer } from '../types';
import OfferTable from '../components/OfferTable';

export default function Viewer() {
  const [offers, setOffers] = useState<Offer[]>([]);
  const [loading, setLoading] = useState(true);
  const [error, setError] = useState('');

  useEffect(() => {
    async function load() {
      const { data, error } = await supabase
        .from('offers')
        .select('*')
        .order('sr', { ascending: true });

      if (error) {
        setError('Failed to load offers. Please try again later.');
      } else {
        setOffers(data ?? []);
      }
      setLoading(false);
    }
    load();
  }, []);

  return (
    <div className="min-h-screen bg-gray-50">
      {/* Header */}
      <header className="bg-[#0f1a35] text-white py-6 px-4 shadow-lg">
        <div className="max-w-screen-xl mx-auto text-center">
          <h1 className="text-2xl md:text-3xl font-bold tracking-tight">
            TrustedNutraProduct
          </h1>
          <p className="text-blue-200 text-sm mt-1 font-medium">Affiliate Offer Directory</p>
          <div className="flex flex-col sm:flex-row items-center justify-center gap-3 mt-3 text-xs text-blue-300">
            <span>✉ contact@trustednutraproduct.com</span>
            <span className="hidden sm:inline">•</span>
            <span>@TrustedNutraProduct</span>
          </div>
        </div>
      </header>

      {/* Content */}
      <main className="max-w-screen-xl mx-auto px-4 py-8">
        {loading && (
          <div className="text-center py-16 text-gray-400">
            <div className="inline-block w-8 h-8 border-4 border-gray-200 border-t-blue-500 rounded-full animate-spin mb-3" />
            <p>Loading offers…</p>
          </div>
        )}

        {error && (
          <div className="text-center py-8 text-red-500 font-medium">{error}</div>
        )}

        {!loading && !error && <OfferTable offers={offers} />}

        {!loading && !error && offers.length > 0 && (
          <p className="text-center text-xs text-gray-400 mt-6">
            Showing {offers.length} offer{offers.length !== 1 ? 's' : ''} · Last updated on page load
          </p>
        )}
      </main>
    </div>
  );
}
