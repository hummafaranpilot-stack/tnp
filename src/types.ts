export interface TopLander {
  label: string;
  url: string;
}

export interface Offer {
  id: string;
  sr: number;
  platform: string;
  offer_name: string;
  offer_id: string;
  category: string;
  top_landers: TopLander[];
  affiliate_page_url: string;
  revshare: string;
  cpa: string;
  allowed_geos: string;
  restriction: string;
  created_at?: string;
  updated_at?: string;
}

export type OfferFormData = Omit<Offer, 'id' | 'created_at' | 'updated_at'>;
