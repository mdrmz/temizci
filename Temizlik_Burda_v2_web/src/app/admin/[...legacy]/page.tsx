import { redirect } from "next/navigation";

interface AdminLegacyPageProps {
  params: Promise<{
    legacy?: string[];
  }>;
}

export default async function AdminLegacyPage({ params }: AdminLegacyPageProps) {
  const { legacy = [] } = await params;
  const firstSegment = legacy[0]?.toLowerCase() ?? "";

  if (firstSegment === "login") {
    redirect("/login");
  }

  redirect("/admin");
}
