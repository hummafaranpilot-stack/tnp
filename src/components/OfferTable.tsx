import { Offer } from '../types';
import PlatformBadge from './PlatformBadge';
import CategoryPill from './CategoryPill';

interface Props {
  offers: Offer[];
}

export default function OfferTable({ offers }: Props) {
  if (offers.length === 0) {
    return (
      <div className="text-center py-16 text-gray-400 text-sm">
        No offers available yet.
      </div>
    );
  }

  return (
    <div className="overflow-x-auto rounded-lg shadow-sm border border-gray-200">
      <table className="min-w-full text-sm">
        <thead>
          <tr className="bg-[#0f1a35] text-white">
            <th className="px-4 py-3 text-left font-semibold whitespace-nowrap">Sr</th>
            <th className="px-4 py-3 text-left font-semibold whitespace-nowrap">Platform</th>
            <th className="px-4 py-3 text-left font-semibold whitespace-nowrap">Offer Name</th>
            <th className="px-4 py-3 text-left font-semibold whitespace-nowrap">Offer ID / Nickname</th>
            <th className="px-4 py-3 text-left font-semibold whitespace-nowrap">Category</th>
            <th className="px-4 py-3 text-left font-semibold whitespace-nowrap">Top Landers</th>
            <th className="px-4 py-3 text-left font-semibold whitespace-nowrap">Affiliate / Creative Page</th>
            <th className="px-4 py-3 text-left font-semibold whitespace-nowrap">RevShare</th>
            <th className="px-4 py-3 text-left font-semibold whitespace-nowrap">CPA</th>
            <th className="px-4 py-3 text-left font-semibold whitespace-nowrap">Allowed GEOs</th>
            <th className="px-4 py-3 text-left font-semibold whitespace-nowrap">Restriction</th>
          </tr>
        </thead>
        <tbody>
          {offers.map((offer, idx) => (
            <tr
              key={offer.id}
              className={idx % 2 === 0 ? 'bg-white' : 'bg-gray-50'}
            >
              <td className="px-4 py-3 text-gray-600 font-medium">{offer.sr}</td>
              <td className="px-4 py-3">
                <PlatformBadge platform={offer.platform} />
              </td>
              <td className="px-4 py-3 font-semibold text-gray-800 whitespace-nowrap">{offer.offer_name}</td>
              <td className="px-4 py-3 text-gray-600">{offer.offer_id}</td>
              <td className="px-4 py-3">
                <CategoryPill category={offer.category} />
              </td>
              <td className="px-4 py-3">
                <div className="flex flex-col gap-1">
                  {offer.top_landers?.map((lander, i) => (
                    <a
                      key={i}
                      href={lander.url}
                      target="_blank"
                      rel="noopener noreferrer"
                      className="text-blue-600 underline hover:text-blue-800 whitespace-nowrap"
                    >
                      {lander.label}
                    </a>
                  ))}
                </div>
              </td>
              <td className="px-4 py-3">
                {offer.affiliate_page_url ? (
                  <a
                    href={offer.affiliate_page_url}
                    target="_blank"
                    rel="noopener noreferrer"
                    className="text-blue-600 underline hover:text-blue-800 whitespace-nowrap"
                  >
                    Click Here
                  </a>
                ) : (
                  <span className="text-gray-400">—</span>
                )}
              </td>
              <td className="px-4 py-3 font-semibold text-green-700">{offer.revshare}</td>
              <td className="px-4 py-3 text-gray-700">{offer.cpa}</td>
              <td className="px-4 py-3 text-gray-600">{offer.allowed_geos}</td>
              <td className="px-4 py-3">
                <span className={offer.restriction === 'No' || !offer.restriction
                  ? 'text-green-600 font-medium'
                  : 'text-red-600 font-medium'}>
                  {offer.restriction || 'No'}
                </span>
              </td>
            </tr>
          ))}
        </tbody>
      </table>
    </div>
  );
}
