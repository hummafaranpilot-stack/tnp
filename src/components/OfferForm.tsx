import { useState, useEffect } from 'react';
import { Offer, OfferFormData, TopLander } from '../types';

interface Props {
  offer?: Offer | null;
  onSave: (data: OfferFormData) => Promise<void>;
  onClose: () => void;
}

const empty: OfferFormData = {
  sr: 0,
  platform: 'BuyGoods',
  offer_name: '',
  offer_id: '',
  category: 'Weight Loss',
  top_landers: [],
  affiliate_page_url: '',
  revshare: '',
  cpa: '',
  allowed_geos: 'Tier-1',
  restriction: 'No',
};

export default function OfferForm({ offer, onSave, onClose }: Props) {
  const [form, setForm] = useState<OfferFormData>(empty);
  const [saving, setSaving] = useState(false);
  const [landerInput, setLanderInput] = useState({ label: '', url: '' });

  useEffect(() => {
    if (offer) {
      const { id: _id, created_at: _c, updated_at: _u, ...rest } = offer;
      void _id; void _c; void _u;
      setForm(rest);
    } else {
      setForm(empty);
    }
  }, [offer]);

  const set = (field: keyof OfferFormData, value: unknown) =>
    setForm(prev => ({ ...prev, [field]: value }));

  const addLander = () => {
    if (!landerInput.label || !landerInput.url) return;
    set('top_landers', [...form.top_landers, { ...landerInput }]);
    setLanderInput({ label: '', url: '' });
  };

  const removeLander = (idx: number) =>
    set('top_landers', form.top_landers.filter((_, i) => i !== idx));

  const handleSubmit = async (e: React.FormEvent) => {
    e.preventDefault();
    setSaving(true);
    try {
      await onSave(form);
    } finally {
      setSaving(false);
    }
  };

  const inputCls = "w-full border border-gray-300 rounded-md px-3 py-2 text-sm focus:outline-none focus:ring-2 focus:ring-blue-500";
  const labelCls = "block text-xs font-semibold text-gray-600 mb-1";

  return (
    <div className="fixed inset-0 bg-black/50 z-50 flex items-center justify-center p-4">
      <div className="bg-white rounded-xl shadow-2xl w-full max-w-2xl max-h-[90vh] overflow-y-auto">
        <div className="bg-[#0f1a35] text-white px-6 py-4 rounded-t-xl flex items-center justify-between">
          <h2 className="text-lg font-bold">{offer ? 'Edit Offer' : 'Add New Offer'}</h2>
          <button onClick={onClose} className="text-white/70 hover:text-white text-2xl leading-none">&times;</button>
        </div>

        <form onSubmit={handleSubmit} className="p-6 grid grid-cols-2 gap-4">
          <div>
            <label className={labelCls}>Sr #</label>
            <input type="number" className={inputCls} value={form.sr}
              onChange={e => set('sr', Number(e.target.value))} required />
          </div>

          <div>
            <label className={labelCls}>Platform</label>
            <select className={inputCls} value={form.platform}
              onChange={e => set('platform', e.target.value)}>
              <option>BuyGoods</option>
              <option>ClickBank</option>
              <option>Other</option>
            </select>
          </div>

          <div className="col-span-2">
            <label className={labelCls}>Offer Name</label>
            <input type="text" className={inputCls} value={form.offer_name}
              onChange={e => set('offer_name', e.target.value)} required placeholder="e.g. MetaTrim BHB" />
          </div>

          <div>
            <label className={labelCls}>Offer ID / Nickname</label>
            <input type="text" className={inputCls} value={form.offer_id}
              onChange={e => set('offer_id', e.target.value)} placeholder="e.g. 11943" />
          </div>

          <div>
            <label className={labelCls}>Category</label>
            <select className={inputCls} value={form.category}
              onChange={e => set('category', e.target.value)}>
              <option>Weight Loss</option>
              <option>Male Enhancement</option>
              <option>Blood Sugar</option>
              <option>Brain Health</option>
              <option>Joint Pain</option>
              <option>Other</option>
            </select>
          </div>

          <div>
            <label className={labelCls}>RevShare</label>
            <input type="text" className={inputCls} value={form.revshare}
              onChange={e => set('revshare', e.target.value)} placeholder="e.g. 75%" />
          </div>

          <div>
            <label className={labelCls}>CPA</label>
            <input type="text" className={inputCls} value={form.cpa}
              onChange={e => set('cpa', e.target.value)} placeholder="e.g. $170" />
          </div>

          <div>
            <label className={labelCls}>Allowed GEOs</label>
            <input type="text" className={inputCls} value={form.allowed_geos}
              onChange={e => set('allowed_geos', e.target.value)} placeholder="e.g. Tier-1" />
          </div>

          <div>
            <label className={labelCls}>Restriction</label>
            <select className={inputCls} value={form.restriction}
              onChange={e => set('restriction', e.target.value)}>
              <option>No</option>
              <option>Yes</option>
            </select>
          </div>

          <div className="col-span-2">
            <label className={labelCls}>Affiliate / Creative Page URL</label>
            <input type="url" className={inputCls} value={form.affiliate_page_url}
              onChange={e => set('affiliate_page_url', e.target.value)} placeholder="https://..." />
          </div>

          <div className="col-span-2">
            <label className={labelCls}>Top Landers</label>
            <div className="flex gap-2 mb-2">
              <input type="text" placeholder="Label (e.g. Lander 1)"
                className="flex-1 border border-gray-300 rounded-md px-3 py-2 text-sm focus:outline-none focus:ring-2 focus:ring-blue-500"
                value={landerInput.label}
                onChange={e => setLanderInput(p => ({ ...p, label: e.target.value }))} />
              <input type="url" placeholder="URL"
                className="flex-1 border border-gray-300 rounded-md px-3 py-2 text-sm focus:outline-none focus:ring-2 focus:ring-blue-500"
                value={landerInput.url}
                onChange={e => setLanderInput(p => ({ ...p, url: e.target.value }))} />
              <button type="button" onClick={addLander}
                className="bg-blue-600 text-white px-3 py-2 rounded-md text-sm font-semibold hover:bg-blue-700">
                + Add
              </button>
            </div>
            <div className="flex flex-col gap-1">
              {form.top_landers.map((l: TopLander, i: number) => (
                <div key={i} className="flex items-center justify-between bg-gray-50 border border-gray-200 rounded px-3 py-1.5 text-sm">
                  <span className="font-medium text-gray-700">{l.label}</span>
                  <span className="text-blue-600 text-xs truncate max-w-xs mx-2">{l.url}</span>
                  <button type="button" onClick={() => removeLander(i)}
                    className="text-red-500 hover:text-red-700 text-xs font-bold">✕</button>
                </div>
              ))}
            </div>
          </div>

          <div className="col-span-2 flex justify-end gap-3 pt-2 border-t border-gray-100">
            <button type="button" onClick={onClose}
              className="px-5 py-2 rounded-lg border border-gray-300 text-sm font-semibold text-gray-600 hover:bg-gray-50">
              Cancel
            </button>
            <button type="submit" disabled={saving}
              className="px-5 py-2 rounded-lg bg-[#0f1a35] text-white text-sm font-semibold hover:bg-[#1a2744] disabled:opacity-60">
              {saving ? 'Saving…' : 'Save Offer'}
            </button>
          </div>
        </form>
      </div>
    </div>
  );
}
