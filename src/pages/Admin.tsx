import { useEffect, useState } from 'react';
import { supabase } from '../lib/supabase';
import { Offer, OfferFormData } from '../types';
import { ADMIN_PASSWORD } from '../config';
import AdminOfferTable from '../components/AdminOfferTable';
import OfferForm from '../components/OfferForm';

const SESSION_KEY = 'tnp_admin';

export default function Admin() {
  const [authed, setAuthed] = useState(() => sessionStorage.getItem(SESSION_KEY) === 'true');
  const [pwInput, setPwInput] = useState('');
  const [pwError, setPwError] = useState('');

  const [offers, setOffers] = useState<Offer[]>([]);
  const [loading, setLoading] = useState(false);
  const [showForm, setShowForm] = useState(false);
  const [editingOffer, setEditingOffer] = useState<Offer | null>(null);
  const [deleteTarget, setDeleteTarget] = useState<Offer | null>(null);
  const [deleting, setDeleting] = useState(false);

  const login = (e: React.FormEvent) => {
    e.preventDefault();
    if (pwInput === ADMIN_PASSWORD) {
      sessionStorage.setItem(SESSION_KEY, 'true');
      setAuthed(true);
    } else {
      setPwError('Incorrect password. Please try again.');
    }
  };

  const logout = () => {
    sessionStorage.removeItem(SESSION_KEY);
    setAuthed(false);
  };

  const loadOffers = async () => {
    setLoading(true);
    const { data } = await supabase
      .from('offers')
      .select('*')
      .order('sr', { ascending: true });
    setOffers(data ?? []);
    setLoading(false);
  };

  useEffect(() => {
    if (authed) loadOffers();
  }, [authed]);

  const handleSave = async (data: OfferFormData) => {
    if (editingOffer) {
      await supabase.from('offers').update({ ...data, updated_at: new Date().toISOString() }).eq('id', editingOffer.id);
    } else {
      await supabase.from('offers').insert([data]);
    }
    setShowForm(false);
    setEditingOffer(null);
    await loadOffers();
  };

  const handleEdit = (offer: Offer) => {
    setEditingOffer(offer);
    setShowForm(true);
  };

  const handleDelete = async () => {
    if (!deleteTarget) return;
    setDeleting(true);
    await supabase.from('offers').delete().eq('id', deleteTarget.id);
    setDeleteTarget(null);
    setDeleting(false);
    await loadOffers();
  };

  if (!authed) {
    return (
      <div className="min-h-screen bg-[#0f1a35] flex items-center justify-center px-4">
        <div className="bg-white rounded-2xl shadow-2xl w-full max-w-sm p-8">
          <div className="text-center mb-6">
            <h1 className="text-xl font-bold text-gray-900">TNP Admin</h1>
            <p className="text-sm text-gray-500 mt-1">Enter your admin password to continue</p>
          </div>
          <form onSubmit={login} className="flex flex-col gap-4">
            <input
              type="password"
              placeholder="Admin password"
              value={pwInput}
              onChange={e => { setPwInput(e.target.value); setPwError(''); }}
              className="border border-gray-300 rounded-lg px-4 py-2.5 text-sm focus:outline-none focus:ring-2 focus:ring-blue-500"
              autoFocus
            />
            {pwError && <p className="text-red-500 text-xs -mt-2">{pwError}</p>}
            <button
              type="submit"
              className="bg-[#0f1a35] text-white rounded-lg py-2.5 text-sm font-semibold hover:bg-[#1a2744]"
            >
              Login
            </button>
          </form>
        </div>
      </div>
    );
  }

  return (
    <div className="min-h-screen bg-gray-50">
      {/* Header */}
      <header className="bg-[#0f1a35] text-white px-6 py-4 shadow-lg">
        <div className="max-w-screen-xl mx-auto flex items-center justify-between">
          <div>
            <h1 className="text-lg font-bold">TNP Admin Dashboard</h1>
            <p className="text-blue-300 text-xs">TrustedNutraProduct</p>
          </div>
          <div className="flex items-center gap-3">
            <a href="/" className="text-blue-200 text-sm hover:text-white">← View Public Page</a>
            <button onClick={logout}
              className="bg-white/10 hover:bg-white/20 text-white text-sm px-4 py-1.5 rounded-lg font-medium">
              Logout
            </button>
          </div>
        </div>
      </header>

      {/* Main */}
      <main className="max-w-screen-xl mx-auto px-4 py-8">
        <div className="flex items-center justify-between mb-6">
          <h2 className="text-xl font-bold text-gray-800">
            Offers <span className="text-gray-400 font-normal text-base">({offers.length})</span>
          </h2>
          <button
            onClick={() => { setEditingOffer(null); setShowForm(true); }}
            className="bg-[#0f1a35] text-white px-5 py-2 rounded-lg text-sm font-semibold hover:bg-[#1a2744] shadow-sm"
          >
            + Add Offer
          </button>
        </div>

        {loading ? (
          <div className="text-center py-12 text-gray-400">Loading…</div>
        ) : (
          <AdminOfferTable
            offers={offers}
            onEdit={handleEdit}
            onDelete={setDeleteTarget}
          />
        )}
      </main>

      {/* Add/Edit Modal */}
      {showForm && (
        <OfferForm
          offer={editingOffer}
          onSave={handleSave}
          onClose={() => { setShowForm(false); setEditingOffer(null); }}
        />
      )}

      {/* Delete Confirm Modal */}
      {deleteTarget && (
        <div className="fixed inset-0 bg-black/50 z-50 flex items-center justify-center px-4">
          <div className="bg-white rounded-xl shadow-2xl p-6 max-w-sm w-full">
            <h3 className="text-lg font-bold text-gray-800 mb-2">Delete Offer?</h3>
            <p className="text-sm text-gray-600 mb-6">
              Are you sure you want to delete <strong>{deleteTarget.offer_name}</strong>? This cannot be undone.
            </p>
            <div className="flex justify-end gap-3">
              <button
                onClick={() => setDeleteTarget(null)}
                className="px-4 py-2 rounded-lg border border-gray-300 text-sm font-semibold text-gray-600 hover:bg-gray-50"
              >
                Cancel
              </button>
              <button
                onClick={handleDelete}
                disabled={deleting}
                className="px-4 py-2 rounded-lg bg-red-600 text-white text-sm font-semibold hover:bg-red-700 disabled:opacity-60"
              >
                {deleting ? 'Deleting…' : 'Delete'}
              </button>
            </div>
          </div>
        </div>
      )}
    </div>
  );
}
