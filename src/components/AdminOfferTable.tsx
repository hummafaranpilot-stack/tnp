import { Offer } from '../types';
import PlatformBadge from './PlatformBadge';
import CategoryPill from './CategoryPill';

interface Props {
  offers: Offer[];
  onEdit: (offer: Offer) => void;
  onDelete: (offer: Offer) => void;
}

export default function AdminOfferTable({ offers, onEdit, onDelete }: Props) {
  if (offers.length === 0) {
    return (
      <div className="text-center py-12 text-gray-400 text-sm border border-dashed border-gray-300 rounded-lg">
        No offers yet. Click "Add Offer" to get started.
      </div>
    );
  }

  return (
    <div className="overflow-x-auto rounded-lg border border-gray-200 shadow-sm">
      <table className="min-w-full text-sm">
        <thead>
          <tr className="bg-[#0f1a35] text-white">
            <th className="px-4 py-3 text-left font-semibold">Sr</th>
            <th className="px-4 py-3 text-left font-semibold">Platform</th>
            <th className="px-4 py-3 text-left font-semibold">Offer Name</th>
            <th className="px-4 py-3 text-left font-semibold">Category</th>
            <th className="px-4 py-3 text-left font-semibold">RevShare</th>
            <th className="px-4 py-3 text-left font-semibold">CPA</th>
            <th className="px-4 py-3 text-left font-semibold">GEOs</th>
            <th className="px-4 py-3 text-center font-semibold">Actions</th>
          </tr>
        </thead>
        <tbody>
          {offers.map((offer, idx) => (
            <tr key={offer.id} className={idx % 2 === 0 ? 'bg-white' : 'bg-gray-50'}>
              <td className="px-4 py-3 text-gray-500">{offer.sr}</td>
              <td className="px-4 py-3"><PlatformBadge platform={offer.platform} /></td>
              <td className="px-4 py-3 font-semibold text-gray-800 whitespace-nowrap">{offer.offer_name}</td>
              <td className="px-4 py-3"><CategoryPill category={offer.category} /></td>
              <td className="px-4 py-3 text-green-700 font-semibold">{offer.revshare}</td>
              <td className="px-4 py-3 text-gray-700">{offer.cpa}</td>
              <td className="px-4 py-3 text-gray-600">{offer.allowed_geos}</td>
              <td className="px-4 py-3 text-center whitespace-nowrap">
                <button
                  onClick={() => onEdit(offer)}
                  className="inline-flex items-center px-3 py-1 rounded-md bg-blue-50 text-blue-700 border border-blue-200 text-xs font-semibold hover:bg-blue-100 mr-2"
                >
                  Edit
                </button>
                <button
                  onClick={() => onDelete(offer)}
                  className="inline-flex items-center px-3 py-1 rounded-md bg-red-50 text-red-600 border border-red-200 text-xs font-semibold hover:bg-red-100"
                >
                  Delete
                </button>
              </td>
            </tr>
          ))}
        </tbody>
      </table>
    </div>
  );
}
