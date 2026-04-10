'use client';

import { useState } from 'react';
import { fastifyClient } from '@/lib/api/client';
import { Card, CardContent, CardDescription, CardHeader, CardTitle } from '@/components/ui/card';
import { Button } from '@/components/ui/button';
import { Input } from '@/components/ui/input';
import { Label } from '@/components/ui/label';
import { Textarea } from '@/components/ui/textarea';

interface Spot {
  uid: string;
  name: string;
  address: string | null;
  telephone: string | null;
  location: { lat: number; lng: number } | null;
  overall_rating: string | null;
  tag: string | null;
  description: string | null;
}

interface SpotsResult {
  success: boolean;
  region: string;
  total: number;
  data: Spot[];
}

interface SubSpotsResult {
  success: boolean;
  name: string;
  region: string;
  radius: number;
  main: Spot;
  total: number;
  subSpots: Spot[];
}

interface PolygonSpot {
  uid: string;
  name: string | null;
  tag: string | null;
  overall_rating: string | null;
  address: string | null;
  location: { lat: number; lng: number } | null;
  telephone: string | null;
  street_id: string | null;
  alias: string | null;
  shop_hours: string | null;
  price: string | null;
  best_time: string | null;
  sug_time: string | null;
  description: string | null;
  photos: string[];
  status: string | null;
}

interface DetailChild {
  uid: string | null;
  name: string | null;
  show_name: string | null;
  location: { lat: number; lng: number } | null;
  std_tag: string | null;
  classified_poi_tag: string | null;
  distance: number | null;
  pv_rate: string | null;
  content_info: string | null;
}

interface DetailInfo {
  tag: string | null;
  classified_poi_tag: string | null;
  type: string | null;
  detail_url: string | null;
  shop_hours: string | null;
  price: string | null;
  overall_rating: string | null;
  image_num: string | null;
  comment_num: string | null;
  brand: string | null;
  indoor_floor: string | null;
  ranking: string | null;
  parent_id: string | null;
  best_time: string | null;
  sug_time: string | null;
  description: string | null;
  navi_location: object | null;
  photos: string[];
  children: DetailChild[];
}

interface DetailSpot {
  uid: string;
  name: string | null;
  location: { lat: number; lng: number } | null;
  province: string | null;
  city: string | null;
  area: string | null;
  address: string | null;
  status: string | null;
  telephone: string | null;
  street_id: string | null;
  detail: string | null;
  detail_info: DetailInfo;
}

interface DetailResult {
  success: boolean;
  total: number;
  data: DetailSpot[];
}

interface PolygonResult {
  success: boolean;
  total: number;
  data: PolygonSpot[];
}

interface SuggestionChild {
  uid: string | null;
  show_name: string | null;
  std_tag: string | null;
  classified_poi_tag: string | null;
  location: { lat: number; lng: number } | null;
  distance: number | null;
  pv_rate: string | null;
  content_info: string | null;
}

interface SuggestionSpot {
  uid: string | null;
  name: string | null;
  location: { lat: number; lng: number } | null;
  province: string | null;
  city: string | null;
  district: string | null;
  address: string | null;
  tag: string | null;
  classified_poi_tag: string | null;
  children: SuggestionChild[];
}

interface SuggestionResult {
  success: boolean;
  total: number;
  data: SuggestionSpot[];
}

interface AdminRegionDetailInfo {
  classified_poi_tag: string | null;
  new_alias: string | null;
  type: string | null;
  detail_url: string | null;
  shop_hours: string | null;
  price: string | null;
  label: string | null;
  overall_rating: string | null;
  image_num: string | null;
  comment_num: string | null;
  brand: string | null;
  indoor_floor: string | null;
  ranking: string | null;
  parent_id: string | null;
  best_time: string | null;
  sug_time: string | null;
  description: string | null;
  d7_hot_value: number | null;
  navi_location: object | null;
  photos: string[];
  children: { uid: string | null; name: string | null; show_name: string | null; classified_poi_tag: string | null; location: { lat: number; lng: number } | null; address: string | null }[];
}

interface AdminRegionSpot {
  uid: string | null;
  name: string | null;
  location: { lat: number; lng: number } | null;
  province: string | null;
  city: string | null;
  area: string | null;
  address: string | null;
  status: string | null;
  telephone: string | null;
  street_id: string | null;
  detail_info: AdminRegionDetailInfo;
}

interface AdminRegionResult {
  success: boolean;
  total: number;
  data: AdminRegionSpot[];
}

type Mode = 'region' | 'sub' | 'polygon' | 'detail' | 'suggestion' | 'admin-region';

function SpotCard({ spot, index }: { spot: Spot; index: number }) {
  return (
    <div className="rounded-lg border bg-card p-4 transition-shadow hover:shadow-md">
      <div className="mb-2 flex items-start justify-between gap-2">
        <div className="flex items-center gap-2">
          <span className="flex h-6 w-6 shrink-0 items-center justify-center rounded-full bg-primary/10 text-xs font-bold text-primary">
            {index}
          </span>
          <h3 className="font-semibold leading-tight">{spot.name}</h3>
        </div>
        {spot.overall_rating && (
          <span className="shrink-0 rounded-full bg-amber-100 px-2 py-0.5 text-xs font-medium text-amber-700 dark:bg-amber-900/30 dark:text-amber-400">
            ★ {spot.overall_rating}
          </span>
        )}
      </div>

      <div className="space-y-1 text-sm text-muted-foreground">
        {spot.address && (
          <div className="flex gap-1.5">
            <span className="shrink-0">📍</span>
            <span>{spot.address}</span>
          </div>
        )}
        {spot.telephone && (
          <div className="flex gap-1.5">
            <span className="shrink-0">📞</span>
            <span>{spot.telephone}</span>
          </div>
        )}
        {spot.tag && (
          <div className="flex gap-1.5">
            <span className="shrink-0">🏷️</span>
            <span>{spot.tag}</span>
          </div>
        )}
        {spot.description && (
          <div className="flex gap-1.5">
            <span className="shrink-0">📝</span>
            <span className="line-clamp-2">{spot.description}</span>
          </div>
        )}
        {spot.location && (
          <div className="flex gap-1.5 text-xs opacity-60">
            <span className="shrink-0">🗺️</span>
            <span>{spot.location.lat.toFixed(5)}, {spot.location.lng.toFixed(5)}</span>
          </div>
        )}
      </div>
    </div>
  );
}

function PolygonSpotCard({
  spot,
  index,
  scenicArea,
  parentSpot,
}: {
  spot: PolygonSpot;
  index: number;
  scenicArea: string;
  parentSpot: string;
}) {
  const [showPhotos, setShowPhotos] = useState(false);

  const fields: { label: string; value: string | null | undefined }[] = [
    { label: '所属景区', value: scenicArea || null },
    { label: '上级景点', value: parentSpot || null },
    { label: '标签',     value: spot.tag },
    { label: '评分',     value: spot.overall_rating },
    { label: '地址',     value: spot.address },
    { label: '经纬度',   value: spot.location ? `${spot.location.lat}, ${spot.location.lng}` : null },
    { label: '电话',     value: spot.telephone },
    { label: '街景图ID', value: spot.street_id },
    { label: '别名',     value: spot.alias },
    { label: '营业时间', value: spot.shop_hours },
    { label: '价格',     value: spot.price },
    { label: '最佳游玩时间', value: spot.best_time },
    { label: '建议时长', value: spot.sug_time },
    { label: '描述',     value: spot.description },
    { label: '营业状态', value: spot.status },
  ];

  return (
    <div className="rounded-lg border bg-card p-4 shadow-sm">
      {/* 标题行 */}
      <div className="mb-3 flex items-center justify-between gap-2">
        <div className="flex items-center gap-2">
          <span className="flex h-6 w-6 shrink-0 items-center justify-center rounded-full bg-primary/10 text-xs font-bold text-primary">
            {index}
          </span>
          <h3 className="font-semibold">{spot.name}</h3>
        </div>
        {spot.overall_rating && (
          <span className="shrink-0 rounded-full bg-amber-100 px-2 py-0.5 text-xs font-medium text-amber-700 dark:bg-amber-900/30 dark:text-amber-400">
            ★ {spot.overall_rating}
          </span>
        )}
      </div>

      {/* 字段网格 */}
      <dl className="grid grid-cols-2 gap-x-4 gap-y-1.5 text-sm">
        {fields.map(({ label, value }) =>
          value ? (
            <div key={label} className={label === '描述' || label === '地址' ? 'col-span-2' : ''}>
              <dt className="text-xs font-medium text-muted-foreground">{label}</dt>
              <dd className="mt-0.5 text-foreground">{value}</dd>
            </div>
          ) : null
        )}
      </dl>

      {/* 图片 */}
      {spot.photos.length > 0 && (
        <div className="mt-3">
          <button
            className="text-xs text-primary underline"
            onClick={() => setShowPhotos(v => !v)}
          >
            {showPhotos ? '收起图片' : `查看 ${spot.photos.length} 张图片`}
          </button>
          {showPhotos && (
            <ul className="mt-2 space-y-1">
              {spot.photos.map((url, i) => (
                <li key={i}>
                  <a
                    href={url}
                    target="_blank"
                    rel="noopener noreferrer"
                    className="break-all text-xs text-blue-600 underline dark:text-blue-400"
                  >
                    {url}
                  </a>
                </li>
              ))}
            </ul>
          )}
        </div>
      )}
    </div>
  );
}

function AdminRegionSpotCard({ spot, index }: { spot: AdminRegionSpot; index: number }) {
  const [showPhotos, setShowPhotos] = useState(false);
  const [showChildren, setShowChildren] = useState(false);
  const di = spot.detail_info;

  const topFields: { label: string; value: string | null | undefined }[] = [
    { label: 'UID',      value: spot.uid },
    { label: '地址',     value: spot.address },
    { label: '省市区',   value: [spot.province, spot.city, spot.area].filter(Boolean).join(' ') || null },
    { label: '电话',     value: spot.telephone },
    { label: '营业状态', value: spot.status || '正常营业' },
    { label: '街景图ID', value: spot.street_id },
  ];

  const diFields: { label: string; value: string | number | null | undefined }[] = [
    { label: '详细分类',     value: di.classified_poi_tag },
    { label: '别名',         value: di.new_alias },
    { label: '品牌',         value: di.brand },
    { label: '楼层',         value: di.indoor_floor },
    { label: '权威标签',     value: di.label },
    { label: '综合评分',     value: di.overall_rating },
    { label: '榜单',         value: di.ranking },
    { label: '营业时间',     value: di.shop_hours },
    { label: '价格',         value: di.price },
    { label: '图片数',       value: di.image_num },
    { label: '评论数',       value: di.comment_num },
    { label: '7日热度',      value: di.d7_hot_value },
    { label: '父点ID',       value: di.parent_id },
    { label: '最佳游玩时间', value: di.best_time },
    { label: '建议时长',     value: di.sug_time },
    { label: '描述',         value: di.description },
    { label: '详情页',       value: di.detail_url },
  ];

  return (
    <div className="rounded-lg border bg-card p-4 shadow-sm">
      <div className="mb-3 flex items-center justify-between gap-2">
        <div className="flex items-center gap-2">
          <span className="flex h-6 w-6 shrink-0 items-center justify-center rounded-full bg-primary/10 text-xs font-bold text-primary">
            {index}
          </span>
          <div>
            <h3 className="font-semibold leading-tight">{spot.name}</h3>
            {di.classified_poi_tag && (
              <span className="text-xs text-muted-foreground">{di.classified_poi_tag}</span>
            )}
          </div>
        </div>
        {di.overall_rating && (
          <span className="shrink-0 rounded-full bg-amber-100 px-2 py-0.5 text-xs font-medium text-amber-700 dark:bg-amber-900/30 dark:text-amber-400">
            ★ {di.overall_rating}
          </span>
        )}
      </div>

      <dl className="mb-3 grid grid-cols-2 gap-x-4 gap-y-1.5 text-sm">
        {topFields.map(({ label, value }) =>
          value ? (
            <div key={label} className={label === '地址' ? 'col-span-2' : ''}>
              <dt className="text-xs font-medium text-muted-foreground">{label}</dt>
              <dd className="mt-0.5 break-all">{value}</dd>
            </div>
          ) : null
        )}
      </dl>

      {spot.location && (
        <p className="mb-3 text-xs text-muted-foreground">
          🗺️ {spot.location.lat}, {spot.location.lng}
        </p>
      )}

      <dl className="grid grid-cols-2 gap-x-4 gap-y-1.5 text-sm">
        {diFields.map(({ label, value }) =>
          value != null && value !== '' ? (
            <div key={label} className={['描述', '详情页'].includes(label) ? 'col-span-2' : ''}>
              <dt className="text-xs font-medium text-muted-foreground">{label}</dt>
              <dd className="mt-0.5 break-all">{
                label === '详情页'
                  ? <a href={String(value)} target="_blank" rel="noopener noreferrer" className="text-blue-600 underline dark:text-blue-400">{value}</a>
                  : String(value)
              }</dd>
            </div>
          ) : null
        )}
      </dl>

      {di.photos.length > 0 && (
        <div className="mt-3">
          <button className="text-xs text-primary underline" onClick={() => setShowPhotos(v => !v)}>
            {showPhotos ? '收起图片' : `查看 ${di.photos.length} 张图片`}
          </button>
          {showPhotos && (
            <ul className="mt-2 space-y-1">
              {di.photos.map((url, i) => (
                <li key={i}>
                  <a href={url} target="_blank" rel="noopener noreferrer"
                    className="break-all text-xs text-blue-600 underline dark:text-blue-400">{url}</a>
                </li>
              ))}
            </ul>
          )}
        </div>
      )}

      {di.children.length > 0 && (
        <div className="mt-3">
          <button className="text-xs text-primary underline" onClick={() => setShowChildren(v => !v)}>
            {showChildren ? '收起子点' : `查看 ${di.children.length} 个子点`}
          </button>
          {showChildren && (
            <ul className="mt-2 divide-y rounded border text-sm">
              {di.children.map((c, i) => (
                <li key={c.uid ?? i} className="px-3 py-2">
                  <div className="font-medium">{c.name}{c.show_name && c.show_name !== c.name ? `（${c.show_name}）` : ''}</div>
                  <div className="mt-0.5 flex flex-wrap gap-x-3 gap-y-0.5 text-xs text-muted-foreground">
                    {c.uid && <span>UID: {c.uid}</span>}
                    {c.classified_poi_tag && <span>{c.classified_poi_tag}</span>}
                    {c.address && <span>{c.address}</span>}
                    {c.location && <span>{c.location.lat.toFixed(5)}, {c.location.lng.toFixed(5)}</span>}
                  </div>
                </li>
              ))}
            </ul>
          )}
        </div>
      )}
    </div>
  );
}

function SuggestionSpotCard({ spot, index }: { spot: SuggestionSpot; index: number }) {
  const [showChildren, setShowChildren] = useState(false);

  return (
    <div className="rounded-lg border bg-card p-4 shadow-sm">
      <div className="mb-2 flex items-start justify-between gap-2">
        <div className="flex items-center gap-2">
          <span className="flex h-6 w-6 shrink-0 items-center justify-center rounded-full bg-primary/10 text-xs font-bold text-primary">
            {index}
          </span>
          <div>
            <h3 className="font-semibold leading-tight">{spot.name}</h3>
            {spot.classified_poi_tag && (
              <span className="text-xs text-muted-foreground">{spot.classified_poi_tag}</span>
            )}
          </div>
        </div>
        {spot.uid && (
          <span className="shrink-0 rounded bg-muted px-1.5 py-0.5 font-mono text-xs text-muted-foreground">
            {spot.uid}
          </span>
        )}
      </div>

      <dl className="grid grid-cols-2 gap-x-4 gap-y-1 text-sm">
        {[
          { label: '地址',   value: spot.address },
          { label: '城市',   value: [spot.province, spot.city, spot.district].filter(Boolean).join(' ') || null },
          { label: '标签',   value: spot.tag },
          { label: '经纬度', value: spot.location ? `${spot.location.lat}, ${spot.location.lng}` : null },
        ].map(({ label, value }) =>
          value ? (
            <div key={label} className={label === '地址' ? 'col-span-2' : ''}>
              <dt className="text-xs font-medium text-muted-foreground">{label}</dt>
              <dd className="mt-0.5">{value}</dd>
            </div>
          ) : null
        )}
      </dl>

      {spot.children.length > 0 && (
        <div className="mt-3">
          <button className="text-xs text-primary underline" onClick={() => setShowChildren(v => !v)}>
            {showChildren ? '收起子点' : `查看 ${spot.children.length} 个子点`}
          </button>
          {showChildren && (
            <ul className="mt-2 divide-y rounded border text-sm">
              {spot.children.map((c, i) => (
                <li key={c.uid ?? i} className="px-3 py-2">
                  <div className="flex items-center justify-between gap-2">
                    <span className="font-medium">{c.show_name}</span>
                    {c.pv_rate && <span className="text-xs text-muted-foreground">{c.pv_rate}选择</span>}
                  </div>
                  <div className="mt-0.5 flex flex-wrap gap-x-3 gap-y-0.5 text-xs text-muted-foreground">
                    {c.uid && <span>UID: {c.uid}</span>}
                    {c.classified_poi_tag && <span>{c.classified_poi_tag}</span>}
                    {c.distance != null && <span>{c.distance}m</span>}
                    {c.content_info && <span>{c.content_info}</span>}
                    {c.location && <span>{c.location.lat.toFixed(5)}, {c.location.lng.toFixed(5)}</span>}
                  </div>
                </li>
              ))}
            </ul>
          )}
        </div>
      )}
    </div>
  );
}

function DetailSpotCard({ spot, index }: { spot: DetailSpot; index: number }) {
  const [showPhotos, setShowPhotos] = useState(false);
  const [showChildren, setShowChildren] = useState(false);
  const di = spot.detail_info;

  const topFields: { label: string; value: string | null | undefined }[] = [
    { label: 'UID',      value: spot.uid },
    { label: '省份',     value: spot.province },
    { label: '城市',     value: spot.city },
    { label: '区县',     value: spot.area },
    { label: '地址',     value: spot.address },
    { label: '电话',     value: spot.telephone },
    { label: '营业状态', value: spot.status || '正常营业' },
    { label: '街景图ID', value: spot.street_id },
  ];

  const detailFields: { label: string; value: string | null | undefined }[] = [
    { label: '分类标签',     value: di.classified_poi_tag || di.tag },
    { label: '综合评分',     value: di.overall_rating },
    { label: '营业时间',     value: di.shop_hours },
    { label: '价格',         value: di.price },
    { label: '品牌',         value: di.brand },
    { label: '楼层',         value: di.indoor_floor },
    { label: '榜单',         value: di.ranking },
    { label: '父点ID',       value: di.parent_id },
    { label: '图片数',       value: di.image_num },
    { label: '评论数',       value: di.comment_num },
    { label: '最佳游玩时间', value: di.best_time },
    { label: '建议时长',     value: di.sug_time },
    { label: '描述',         value: di.description },
    { label: '详情页',       value: di.detail_url },
  ];

  return (
    <div className="rounded-lg border bg-card p-4 shadow-sm">
      {/* 标题 */}
      <div className="mb-3 flex items-center justify-between gap-2">
        <div className="flex items-center gap-2">
          <span className="flex h-6 w-6 shrink-0 items-center justify-center rounded-full bg-primary/10 text-xs font-bold text-primary">
            {index}
          </span>
          <h3 className="font-semibold">{spot.name}</h3>
        </div>
        {di.overall_rating && (
          <span className="shrink-0 rounded-full bg-amber-100 px-2 py-0.5 text-xs font-medium text-amber-700 dark:bg-amber-900/30 dark:text-amber-400">
            ★ {di.overall_rating}
          </span>
        )}
      </div>

      {/* 基础字段 */}
      <dl className="mb-3 grid grid-cols-2 gap-x-4 gap-y-1.5 text-sm">
        {topFields.map(({ label, value }) =>
          value ? (
            <div key={label} className={label === '地址' ? 'col-span-2' : ''}>
              <dt className="text-xs font-medium text-muted-foreground">{label}</dt>
              <dd className="mt-0.5 break-all">{value}</dd>
            </div>
          ) : null
        )}
      </dl>

      {/* 坐标 */}
      {spot.location && (
        <p className="mb-3 text-xs text-muted-foreground">
          🗺️ {spot.location.lat}, {spot.location.lng}
        </p>
      )}

      {/* 详情字段 */}
      <dl className="grid grid-cols-2 gap-x-4 gap-y-1.5 text-sm">
        {detailFields.map(({ label, value }) =>
          value ? (
            <div key={label} className={label === '描述' || label === '详情页' ? 'col-span-2' : ''}>
              <dt className="text-xs font-medium text-muted-foreground">{label}</dt>
              <dd className="mt-0.5 break-all">{
                label === '详情页'
                  ? <a href={value} target="_blank" rel="noopener noreferrer" className="text-blue-600 underline dark:text-blue-400">{value}</a>
                  : value
              }</dd>
            </div>
          ) : null
        )}
      </dl>

      {/* 图片 */}
      {di.photos.length > 0 && (
        <div className="mt-3">
          <button className="text-xs text-primary underline" onClick={() => setShowPhotos(v => !v)}>
            {showPhotos ? '收起图片' : `查看 ${di.photos.length} 张图片`}
          </button>
          {showPhotos && (
            <ul className="mt-2 space-y-1">
              {di.photos.map((url, i) => (
                <li key={i}>
                  <a href={url} target="_blank" rel="noopener noreferrer"
                    className="break-all text-xs text-blue-600 underline dark:text-blue-400">{url}</a>
                </li>
              ))}
            </ul>
          )}
        </div>
      )}

      {/* 子点 */}
      {di.children.length > 0 && (
        <div className="mt-3">
          <button className="text-xs text-primary underline" onClick={() => setShowChildren(v => !v)}>
            {showChildren ? '收起子点' : `查看 ${di.children.length} 个子点`}
          </button>
          {showChildren && (
            <ul className="mt-2 divide-y rounded border text-sm">
              {di.children.map((c, i) => (
                <li key={c.uid ?? i} className="px-3 py-2">
                  <div className="flex items-center justify-between gap-2">
                    <span className="font-medium">{c.name}{c.show_name && c.show_name !== c.name ? `（${c.show_name}）` : ''}</span>
                    {c.pv_rate && <span className="text-xs text-muted-foreground">{c.pv_rate}选择</span>}
                  </div>
                  <div className="mt-0.5 flex flex-wrap gap-x-3 gap-y-0.5 text-xs text-muted-foreground">
                    {c.uid && <span>UID: {c.uid}</span>}
                    {c.classified_poi_tag && <span>{c.classified_poi_tag}</span>}
                    {c.distance != null && <span>{c.distance}m</span>}
                    {c.content_info && <span>{c.content_info}</span>}
                    {c.location && <span>{c.location.lat.toFixed(5)}, {c.location.lng.toFixed(5)}</span>}
                  </div>
                </li>
              ))}
            </ul>
          )}
        </div>
      )}
    </div>
  );
}

export default function ScenicPage() {
  const [mode, setMode] = useState<Mode>('region');

  // region mode
  const [region, setRegion] = useState('');
  // sub-spots mode
  const [spotName, setSpotName] = useState('');
  const [subRegion, setSubRegion] = useState('');
  const [radius, setRadius] = useState('5000');
  const [keywords, setKeywords] = useState('');
  // polygon mode
  const [coordsText, setCoordsText] = useState('');
  const [polyQuery, setPolyQuery] = useState('景区');
  const [polyTag, setPolyTag] = useState('旅游景点');
  const [scenicArea, setScenicArea] = useState('');
  const [parentSpot, setParentSpot] = useState('');
  // detail mode
  const [detailUidsText, setDetailUidsText] = useState('');
  // suggestion mode
  const [sugQuery, setSugQuery] = useState('');
  const [sugRegion, setSugRegion] = useState('');
  const [sugRegionLimit, setSugRegionLimit] = useState(true);
  const [sugLocation, setSugLocation] = useState('');
  const [sugRadius, setSugRadius] = useState('');
  const [sugBounds, setSugBounds] = useState('');
  // admin-region mode
  const [arQuery, setArQuery] = useState('');
  const [arRegion, setArRegion] = useState('');
  const [arRegionLimit, setArRegionLimit] = useState(false);
  const [arType, setArType] = useState('');
  const [arCenter, setArCenter] = useState('');
  const [arFilter, setArFilter] = useState('');
  const [arAutoPages, setArAutoPages] = useState(true);

  const [loading, setLoading] = useState(false);
  const [error, setError] = useState('');

  const [regionResult, setRegionResult] = useState<SpotsResult | null>(null);
  const [subResult, setSubResult] = useState<SubSpotsResult | null>(null);
  const [polygonResult, setPolygonResult] = useState<PolygonResult | null>(null);
  const [detailResult, setDetailResult] = useState<DetailResult | null>(null);
  const [suggestionResult, setSuggestionResult] = useState<SuggestionResult | null>(null);
  const [adminRegionResult, setAdminRegionResult] = useState<AdminRegionResult | null>(null);

  function clearResults() {
    setRegionResult(null);
    setSubResult(null);
    setPolygonResult(null);
    setDetailResult(null);
    setSuggestionResult(null);
    setAdminRegionResult(null);
    setError('');
  }

  async function handleRegionSearch(e: React.FormEvent) {
    e.preventDefault();
    clearResults();
    setLoading(true);
    try {
      const res = await fastifyClient.get<SpotsResult>(
        `/scenic/spots?region=${encodeURIComponent(region)}`
      );
      setRegionResult(res);
    } catch (err) {
      setError(err instanceof Error ? err.message : '查询失败，请重试');
    } finally {
      setLoading(false);
    }
  }

  async function handleSubSearch(e: React.FormEvent) {
    e.preventDefault();
    clearResults();
    setLoading(true);
    try {
      const params = new URLSearchParams({
        name: spotName,
        region: subRegion,
        radius,
        ...(keywords ? { keywords } : {}),
      });
      const res = await fastifyClient.get<SubSpotsResult>(`/scenic/sub-spots?${params}`);
      setSubResult(res);
    } catch (err) {
      setError(err instanceof Error ? err.message : '查询失败，请重试');
    } finally {
      setLoading(false);
    }
  }

  async function handlePolygonSearch(e: React.FormEvent) {
    e.preventDefault();
    clearResults();

    // 将多行 lat,lng 转为百度 API 要求的平铺逗号串
    const pairs = coordsText
      .split('\n')
      .map(l => l.trim())
      .filter(Boolean);

    if (pairs.length < 3) {
      setError('至少需要 3 个顶点坐标');
      return;
    }

    // 确保首尾相同（闭合多边形）
    const closed = pairs[pairs.length - 1] === pairs[0] ? pairs : [...pairs, pairs[0]];
    const bounds = closed.join(',');

    setLoading(true);
    try {
      const params = new URLSearchParams({ bounds, query: polyQuery, tag: polyTag });
      const res = await fastifyClient.get<PolygonResult>(`/scenic/polygon?${params}`);
      setPolygonResult(res);
    } catch (err) {
      setError(err instanceof Error ? err.message : '查询失败，请重试');
    } finally {
      setLoading(false);
    }
  }

  async function handleDetailSearch(e: React.FormEvent) {
    e.preventDefault();
    clearResults();
    const uids = detailUidsText
      .split(/[\n,，]/)
      .map(s => s.trim())
      .filter(Boolean);
    if (uids.length === 0) { setError('请输入至少一个 UID'); return; }
    if (uids.length > 10) { setError('单次最多查询 10 个 UID'); return; }
    setLoading(true);
    try {
      const params = new URLSearchParams({ uids: uids.join(',') });
      const res = await fastifyClient.get<DetailResult>(`/scenic/detail?${params}`);
      setDetailResult(res);
    } catch (err) {
      setError(err instanceof Error ? err.message : '查询失败，请重试');
    } finally {
      setLoading(false);
    }
  }

  async function handleSuggestionSearch(e: React.FormEvent) {
    e.preventDefault();
    clearResults();
    setLoading(true);
    try {
      const params = new URLSearchParams({ query: sugQuery, region: sugRegion });
      if (sugRegionLimit) params.set('region_limit', 'true');
      if (sugLocation.trim()) params.set('location', sugLocation.trim());
      if (sugRadius.trim()) params.set('radius', sugRadius.trim());
      if (sugBounds.trim()) params.set('bounds', sugBounds.trim());
      const res = await fastifyClient.get<SuggestionResult>(`/scenic/suggestion?${params}`);
      setSuggestionResult(res);
    } catch (err) {
      setError(err instanceof Error ? err.message : '查询失败，请重试');
    } finally {
      setLoading(false);
    }
  }

  async function handleAdminRegionSearch(e: React.FormEvent) {
    e.preventDefault();
    clearResults();
    setLoading(true);
    try {
      const params = new URLSearchParams({ query: arQuery, region: arRegion });
      if (arRegionLimit) params.set('region_limit', 'true');
      if (arType.trim())   params.set('type', arType.trim());
      if (arCenter.trim()) params.set('center', arCenter.trim());
      if (arFilter.trim()) params.set('filter', arFilter.trim());
      if (!arAutoPages)    params.set('auto_pages', 'false');
      const res = await fastifyClient.get<AdminRegionResult>(`/scenic/admin-region?${params}`);
      setAdminRegionResult(res);
    } catch (err) {
      setError(err instanceof Error ? err.message : '查询失败，请重试');
    } finally {
      setLoading(false);
    }
  }

  return (
    <div className="min-h-screen bg-gradient-to-b from-green-50 to-emerald-50 dark:from-stone-900 dark:to-stone-800">
      <div className="container mx-auto max-w-4xl px-4 py-10">

        {/* 标题 */}
        <div className="mb-8 text-center">
          <div className="mb-2 text-6xl">🏔️</div>
          <h1 className="mb-2 text-3xl font-bold tracking-wide">景区查询</h1>
          <p className="text-muted-foreground">基于百度地图 API，搜集地区景区清单或探索景区周边子景点</p>
        </div>

        {/* 模式切换 */}
        <div className="mb-6 flex rounded-lg border bg-card p-1">
          {([
            { key: 'region',  label: '🗺️ 地区景区清单' },
            { key: 'sub',     label: '🔍 景区周边子景点' },
            { key: 'polygon', label: '📐 多边形检索' },
            { key: 'detail',     label: '🔎 地点详情检索' },
            { key: 'suggestion',   label: '💡 输入提示' },
            { key: 'admin-region', label: '🏛️ 行政区划检索' },
          ] as { key: Mode; label: string }[]).map(({ key, label }) => (
            <button
              key={key}
              className={`flex-1 rounded-md py-2 text-sm font-medium transition-colors ${
                mode === key
                  ? 'bg-primary text-primary-foreground shadow'
                  : 'text-muted-foreground hover:text-foreground'
              }`}
              onClick={() => { setMode(key); clearResults(); }}
            >
              {label}
            </button>
          ))}
        </div>

        {/* 地区搜索表单 */}
        {mode === 'region' && (
          <Card className="mb-6 border-green-200 dark:border-stone-600">
            <CardHeader>
              <CardTitle>搜索地区景区</CardTitle>
              <CardDescription>输入城市或地区名称，自动搜集所有旅游景点</CardDescription>
            </CardHeader>
            <CardContent>
              <form onSubmit={handleRegionSearch} className="flex gap-3">
                <div className="flex-1 space-y-1">
                  <Label htmlFor="region">地区名称</Label>
                  <Input
                    id="region"
                    placeholder="如：成都、包头市、西藏"
                    value={region}
                    onChange={e => setRegion(e.target.value)}
                    required
                  />
                </div>
                <div className="flex items-end">
                  <Button type="submit" disabled={loading}>
                    {loading ? '搜集中…' : '开始搜索'}
                  </Button>
                </div>
              </form>
              {error && <p className="mt-3 text-sm text-destructive">{error}</p>}
            </CardContent>
          </Card>
        )}

        {/* 子景点搜索表单 */}
        {mode === 'sub' && (
          <Card className="mb-6 border-green-200 dark:border-stone-600">
            <CardHeader>
              <CardTitle>搜索景区周边子景点</CardTitle>
              <CardDescription>输入主景区名称，在其坐标周边搜索子景点</CardDescription>
            </CardHeader>
            <CardContent>
              <form onSubmit={handleSubSearch} className="space-y-4">
                <div className="grid gap-4 sm:grid-cols-2">
                  <div className="space-y-1">
                    <Label htmlFor="spotName">景区名称</Label>
                    <Input
                      id="spotName"
                      placeholder="如：五当召风景区"
                      value={spotName}
                      onChange={e => setSpotName(e.target.value)}
                      required
                    />
                  </div>
                  <div className="space-y-1">
                    <Label htmlFor="subRegion">所在地区</Label>
                    <Input
                      id="subRegion"
                      placeholder="如：包头市"
                      value={subRegion}
                      onChange={e => setSubRegion(e.target.value)}
                      required
                    />
                  </div>
                  <div className="space-y-1">
                    <Label htmlFor="radius">搜索半径（米）</Label>
                    <Input
                      id="radius"
                      type="number"
                      min={100}
                      max={50000}
                      placeholder="默认 5000"
                      value={radius}
                      onChange={e => setRadius(e.target.value)}
                    />
                  </div>
                  <div className="space-y-1">
                    <Label htmlFor="keywords">关键词（逗号分隔，可选）</Label>
                    <Input
                      id="keywords"
                      placeholder="默认：景点,寺庙,佛殿,参观,景区"
                      value={keywords}
                      onChange={e => setKeywords(e.target.value)}
                    />
                  </div>
                </div>
                {error && <p className="text-sm text-destructive">{error}</p>}
                <Button type="submit" className="w-full" disabled={loading}>
                  {loading ? '搜索中…' : '查找子景点'}
                </Button>
              </form>
            </CardContent>
          </Card>
        )}

        {/* 多边形检索表单 */}
        {mode === 'polygon' && (
          <Card className="mb-6 border-green-200 dark:border-stone-600">
            <CardHeader>
              <CardTitle>多边形区域检索</CardTitle>
              <CardDescription>
                输入多边形顶点坐标（每行一个 <code>纬度,经度</code>），自动补充地点详情
              </CardDescription>
            </CardHeader>
            <CardContent>
              <form onSubmit={handlePolygonSearch} className="space-y-4">
                <div className="grid gap-4 sm:grid-cols-2">
                  <div className="space-y-1">
                    <Label htmlFor="scenicArea">所属景区</Label>
                    <Input
                      id="scenicArea"
                      placeholder="如：无棣古城"
                      value={scenicArea}
                      onChange={e => setScenicArea(e.target.value)}
                    />
                  </div>
                  <div className="space-y-1">
                    <Label htmlFor="parentSpot">上级景点（可选）</Label>
                    <Input
                      id="parentSpot"
                      placeholder="如：无棣古城核心区"
                      value={parentSpot}
                      onChange={e => setParentSpot(e.target.value)}
                    />
                  </div>
                  <div className="space-y-1">
                    <Label htmlFor="polyQuery">搜索关键词</Label>
                    <Input
                      id="polyQuery"
                      placeholder="默认：景区"
                      value={polyQuery}
                      onChange={e => setPolyQuery(e.target.value)}
                    />
                  </div>
                  <div className="space-y-1">
                    <Label htmlFor="polyTag">标签筛选</Label>
                    <Input
                      id="polyTag"
                      placeholder="默认：旅游景点"
                      value={polyTag}
                      onChange={e => setPolyTag(e.target.value)}
                    />
                  </div>
                </div>

                <div className="space-y-1">
                  <Label htmlFor="coords">多边形顶点坐标（每行一个 纬度,经度）</Label>
                  <Textarea
                    id="coords"
                    rows={6}
                    placeholder={`37.7744,117.6213\n37.7802,117.6398\n37.7651,117.6421\n37.7622,117.6247`}
                    value={coordsText}
                    onChange={e => setCoordsText(e.target.value)}
                    required
                    className="font-mono text-sm"
                  />
                  <p className="text-xs text-muted-foreground">
                    至少 3 个顶点，系统自动闭合多边形。坐标为百度坐标系（BD09）。
                  </p>
                </div>

                {error && <p className="text-sm text-destructive">{error}</p>}
                <Button type="submit" className="w-full" disabled={loading}>
                  {loading ? '检索中，正在补充详情…' : '开始多边形检索'}
                </Button>
              </form>
            </CardContent>
          </Card>
        )}

        {/* 地点详情检索表单 */}
        {mode === 'detail' && (
          <Card className="mb-6 border-green-200 dark:border-stone-600">
            <CardHeader>
              <CardTitle>地点详情检索</CardTitle>
              <CardDescription>
                输入 POI 的 UID（每行一个，或用逗号分隔），最多 10 个，返回完整详情信息
              </CardDescription>
            </CardHeader>
            <CardContent>
              <form onSubmit={handleDetailSearch} className="space-y-4">
                <div className="space-y-1">
                  <Label htmlFor="detailUids">POI UID</Label>
                  <Textarea
                    id="detailUids"
                    rows={5}
                    placeholder={`435d7aea036e54355abbbcc8\n8ee4560cf91d160e6cc02cd7`}
                    value={detailUidsText}
                    onChange={e => setDetailUidsText(e.target.value)}
                    required
                    className="font-mono text-sm"
                  />
                  <p className="text-xs text-muted-foreground">
                    可从地区检索、多边形检索等结果中复制 UID。
                  </p>
                </div>
                {error && <p className="text-sm text-destructive">{error}</p>}
                <Button type="submit" className="w-full" disabled={loading}>
                  {loading ? '检索中…' : '查询详情'}
                </Button>
              </form>
            </CardContent>
          </Card>
        )}

        {/* 地点输入提示表单 */}
        {mode === 'suggestion' && (
          <Card className="mb-6 border-green-200 dark:border-stone-600">
            <CardHeader>
              <CardTitle>地点输入提示</CardTitle>
              <CardDescription>
                匹配关键词，返回最多 10 条推荐地点，结果含 UID 可用于详情检索
              </CardDescription>
            </CardHeader>
            <CardContent>
              <form onSubmit={handleSuggestionSearch} className="space-y-4">
                <div className="grid gap-4 sm:grid-cols-2">
                  <div className="space-y-1">
                    <Label htmlFor="sugQuery">关键词</Label>
                    <Input
                      id="sugQuery"
                      placeholder="如：无棣古城、天安门、上地"
                      value={sugQuery}
                      onChange={e => setSugQuery(e.target.value)}
                      required
                    />
                  </div>
                  <div className="space-y-1">
                    <Label htmlFor="sugRegion">城市</Label>
                    <Input
                      id="sugRegion"
                      placeholder="如：北京、滨州市"
                      value={sugRegion}
                      onChange={e => setSugRegion(e.target.value)}
                      required
                    />
                  </div>
                  <div className="space-y-1">
                    <Label htmlFor="sugLocation">中心坐标（可选，排序权重）</Label>
                    <Input
                      id="sugLocation"
                      placeholder="纬度,经度  如：37.7744,117.6213"
                      value={sugLocation}
                      onChange={e => setSugLocation(e.target.value)}
                      className="font-mono text-sm"
                    />
                  </div>
                  <div className="space-y-1">
                    <Label htmlFor="sugRadius">搜索半径（米，可选）</Label>
                    <Input
                      id="sugRadius"
                      type="number"
                      min={1}
                      placeholder="默认 1000"
                      value={sugRadius}
                      onChange={e => setSugRadius(e.target.value)}
                    />
                  </div>
                </div>

                <div className="flex items-center gap-2">
                  <input
                    id="sugRegionLimit"
                    type="checkbox"
                    checked={sugRegionLimit}
                    onChange={e => setSugRegionLimit(e.target.checked)}
                    className="h-4 w-4 rounded border-input"
                  />
                  <Label htmlFor="sugRegionLimit" className="cursor-pointer font-normal">
                    严格限制在城市范围内（region_limit）
                  </Label>
                </div>

                {error && <p className="text-sm text-destructive">{error}</p>}
                <Button type="submit" className="w-full" disabled={loading}>
                  {loading ? '查询中…' : '获取输入提示'}
                </Button>
              </form>
            </CardContent>
          </Card>
        )}

        {/* 行政区划区域检索表单 */}
        {mode === 'admin-region' && (
          <Card className="mb-6 border-green-200 dark:border-stone-600">
            <CardHeader>
              <CardTitle>行政区划区域检索</CardTitle>
              <CardDescription>
                检索指定行政区划内（支持到区县级）的 POI，自动翻页最多返回 150 条
              </CardDescription>
            </CardHeader>
            <CardContent>
              <form onSubmit={handleAdminRegionSearch} className="space-y-4">
                <div className="grid gap-4 sm:grid-cols-2">
                  <div className="space-y-1">
                    <Label htmlFor="arQuery">关键词</Label>
                    <Input
                      id="arQuery"
                      placeholder="如：景区、ATM机、美食"
                      value={arQuery}
                      onChange={e => setArQuery(e.target.value)}
                      required
                    />
                  </div>
                  <div className="space-y-1">
                    <Label htmlFor="arRegion">行政区划</Label>
                    <Input
                      id="arRegion"
                      placeholder="如：北京市海淀区、无棣县、滨州市"
                      value={arRegion}
                      onChange={e => setArRegion(e.target.value)}
                      required
                    />
                  </div>
                  <div className="space-y-1">
                    <Label htmlFor="arType">类型筛选（可选）</Label>
                    <Input
                      id="arType"
                      placeholder="如：火锅、旅游景点、酒店"
                      value={arType}
                      onChange={e => setArType(e.target.value)}
                    />
                  </div>
                  <div className="space-y-1">
                    <Label htmlFor="arCenter">中心坐标（可选，辅助距离排序）</Label>
                    <Input
                      id="arCenter"
                      placeholder="纬度,经度  如：37.7744,117.6213"
                      value={arCenter}
                      onChange={e => setArCenter(e.target.value)}
                      className="font-mono text-sm"
                    />
                  </div>
                  <div className="space-y-1 sm:col-span-2">
                    <Label htmlFor="arFilter">排序条件（可选）</Label>
                    <Input
                      id="arFilter"
                      placeholder="如：industry_type:cater|sort_name:overall_rating|sort_rule:0"
                      value={arFilter}
                      onChange={e => setArFilter(e.target.value)}
                      className="font-mono text-sm"
                    />
                  </div>
                </div>

                <div className="flex flex-wrap gap-x-6 gap-y-2">
                  <label className="flex cursor-pointer items-center gap-2">
                    <input
                      type="checkbox"
                      checked={arRegionLimit}
                      onChange={e => setArRegionLimit(e.target.checked)}
                      className="h-4 w-4 rounded border-input"
                    />
                    <span className="text-sm">严格限制在区划范围内</span>
                  </label>
                  <label className="flex cursor-pointer items-center gap-2">
                    <input
                      type="checkbox"
                      checked={arAutoPages}
                      onChange={e => setArAutoPages(e.target.checked)}
                      className="h-4 w-4 rounded border-input"
                    />
                    <span className="text-sm">自动翻页（最多 150 条）</span>
                  </label>
                </div>

                {error && <p className="text-sm text-destructive">{error}</p>}
                <Button type="submit" className="w-full" disabled={loading}>
                  {loading ? '检索中…' : '开始行政区划检索'}
                </Button>
              </form>
            </CardContent>
          </Card>
        )}

        {/* 地区搜索结果 */}
        {regionResult && (
          <div className="space-y-4">
            <div className="flex items-center justify-between">
              <h2 className="text-lg font-semibold">
                「{regionResult.region}」共找到 {regionResult.total} 个景区
              </h2>
              <span className="text-sm text-muted-foreground">数据来源：百度地图</span>
            </div>
            <div className="grid gap-3 sm:grid-cols-2">
              {regionResult.data.map((spot, i) => (
                <SpotCard key={spot.uid} spot={spot} index={i + 1} />
              ))}
            </div>
          </div>
        )}

        {/* 子景点搜索结果 */}
        {subResult && (
          <div className="space-y-4">
            {/* 主景区 */}
            <Card className="border-emerald-300 bg-emerald-50 dark:border-emerald-800 dark:bg-emerald-950/30">
              <CardHeader className="pb-2">
                <CardTitle className="text-base text-emerald-700 dark:text-emerald-400">主景区</CardTitle>
              </CardHeader>
              <CardContent>
                <SpotCard spot={subResult.main} index={0} />
              </CardContent>
            </Card>

            {/* 子景点列表 */}
            <div className="flex items-center justify-between">
              <h2 className="text-lg font-semibold">
                周边 {subResult.radius}m 内共找到 {subResult.total} 个子景点
              </h2>
              <span className="text-sm text-muted-foreground">数据来源：百度地图</span>
            </div>
            <div className="grid gap-3 sm:grid-cols-2">
              {subResult.subSpots.map((spot, i) => (
                <SpotCard key={spot.uid} spot={spot} index={i + 1} />
              ))}
            </div>
          </div>
        )}

        {/* 多边形检索结果 */}
        {polygonResult && (
          <div className="space-y-4">
            <div className="flex items-center justify-between">
              <h2 className="text-lg font-semibold">
                多边形区域内共找到 {polygonResult.total} 个 POI
              </h2>
              <span className="text-sm text-muted-foreground">数据来源：百度地图</span>
            </div>
            <div className="grid gap-4">
              {polygonResult.data.map((spot, i) => (
                <PolygonSpotCard
                  key={spot.uid}
                  spot={spot}
                  index={i + 1}
                  scenicArea={scenicArea}
                  parentSpot={parentSpot}
                />
              ))}
            </div>
          </div>
        )}

        {/* 地点详情检索结果 */}
        {detailResult && (
          <div className="space-y-4">
            <div className="flex items-center justify-between">
              <h2 className="text-lg font-semibold">
                共检索到 {detailResult.total} 个 POI 详情
              </h2>
              <span className="text-sm text-muted-foreground">数据来源：百度地图</span>
            </div>
            <div className="grid gap-4">
              {detailResult.data.map((spot, i) => (
                <DetailSpotCard key={spot.uid} spot={spot} index={i + 1} />
              ))}
            </div>
          </div>
        )}

        {/* 输入提示结果 */}
        {suggestionResult && (
          <div className="space-y-4">
            <div className="flex items-center justify-between">
              <h2 className="text-lg font-semibold">
                共匹配到 {suggestionResult.total} 条提示结果
              </h2>
              <span className="text-sm text-muted-foreground">数据来源：百度地图</span>
            </div>
            <div className="grid gap-3 sm:grid-cols-2">
              {suggestionResult.data.map((spot, i) => (
                <SuggestionSpotCard key={spot.uid ?? i} spot={spot} index={i + 1} />
              ))}
            </div>
          </div>
        )}

        {/* 行政区划检索结果 */}
        {adminRegionResult && (
          <div className="space-y-4">
            <div className="flex items-center justify-between">
              <h2 className="text-lg font-semibold">
                共找到 {adminRegionResult.total} 条结果
              </h2>
              <span className="text-sm text-muted-foreground">数据来源：百度地图</span>
            </div>
            <div className="grid gap-4">
              {adminRegionResult.data.map((spot, i) => (
                <AdminRegionSpotCard key={spot.uid ?? i} spot={spot} index={i + 1} />
              ))}
            </div>
          </div>
        )}

      </div>
    </div>
  );
}
