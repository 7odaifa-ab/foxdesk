#!/usr/bin/env node

import fs from "node:fs";
import path from "node:path";

const args = process.argv.slice(2);
const option = (name, fallback) => {
  const prefix = `--${name}=`;
  return args.find((argument) => argument.startsWith(prefix))?.slice(prefix.length) ?? fallback;
};
const root = path.resolve(option("repo", path.join(import.meta.dirname, "..")));
const catalogDir = path.resolve(root, option("catalog-dir", "locales/catalogs"));
const english = JSON.parse(fs.readFileSync(path.join(catalogDir, "en.json"), "utf8"));

const translations = {
  fa: {
    Manual: "دستی",
    files: "فایل‌ها",
    entries: "ورودی‌ها",
    company: "شرکت",
    comments: "نظرها",
    client: "مشتری",
    action: "اقدام",
    report: "گزارش",
    tags: "برچسب‌ها",
    clients: "مشتریان",
    tickets: "تیکت‌ها",
  },
  fr: { Icon: "Icône", files: "fichiers", type: "type" },
  he: { Icon: "סמל", company: "חברה", report: "דוח", tags: "תגיות" },
  id: { type: "jenis", report: "laporan", tags: "tag" },
  ja: { yesterday: "昨日", "This Month": "今月", "This Year": "今年", What: "内容" },
  pl: {
    Board: "Tablica",
    Icon: "Ikona",
    Prefix: "Prefiks",
    Profile: "Profil",
    selected: "wybrane",
    entries: "wpisy",
    company: "firma",
    client: "klient",
    action: "działanie",
    report: "raport",
    tags: "tagi",
    clients: "klienci",
    tickets: "zgłoszenia",
  },
  "pt-BR": { files: "arquivos", entries: "registros", type: "tipo" },
  "pt-PT": { files: "ficheiros", entries: "entradas", type: "tipo" },
  ru: {
    Icon: "Значок",
    files: "файлы",
    company: "компания",
    "users are": "пользователи",
    report: "отчёт",
  },
  uk: {
    files: "файли",
    selected: "вибрано",
    company: "компанія",
    Channel: "Канал",
    "users are": "користувачі",
    comments: "коментарі",
    agents: "агенти",
  },
  vi: {
    Board: "Bảng",
    files: "tệp",
    entries: "mục",
    company: "công ty",
    Channel: "Kênh",
    action: "hành động",
    report: "báo cáo",
    tags: "thẻ",
    agents: "nhân viên",
    tickets: "phiếu hỗ trợ",
  },
  "zh-Hans": { Prev: "上一页" },
  "zh-Hant": { Prev: "上一頁" },
};

function fill(source, target, locale, trail = []) {
  for (const [key, sourceValue] of Object.entries(source)) {
    if (key === "_meta") continue;
    if (typeof sourceValue === "string") {
      if (typeof target[key] === "string" && target[key].trim() === "") {
        const replacement = translations[locale]?.[sourceValue];
        if (!replacement) {
          throw new Error(
            `Missing manual ${locale} translation for ${trail.concat(key).join(".")}: ${sourceValue}`,
          );
        }
        target[key] = replacement;
      }
      continue;
    }
    if (sourceValue && typeof sourceValue === "object") {
      fill(sourceValue, target[key], locale, [...trail, key]);
    }
  }
}

let changed = 0;
for (const file of fs.readdirSync(catalogDir).filter((name) => name.endsWith(".json") && name !== "en.json")) {
  const locale = file.slice(0, -5);
  const targetPath = path.join(catalogDir, file);
  const target = JSON.parse(fs.readFileSync(targetPath, "utf8"));
  const before = JSON.stringify(target);
  fill(english, target, locale);
  if (JSON.stringify(target) !== before) {
    const indent = fs.readFileSync(targetPath, "utf8").match(/\n( +)"/)?.[1]?.length ?? 2;
    fs.writeFileSync(targetPath, `${JSON.stringify(target, null, indent)}\n`);
    changed += 1;
  }
}
process.stdout.write(`Filled empty translations in ${changed} locale catalogs.\n`);
