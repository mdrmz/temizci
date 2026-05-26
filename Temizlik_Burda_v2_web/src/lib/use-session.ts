"use client";

import { useEffect, useState } from "react";
import { useRouter } from "next/navigation";

import { getSession } from "@/lib/session";
import { Session } from "@/lib/types";

export function useSession(requireAuth = false) {
  const router = useRouter();
  const [ready, setReady] = useState(false);
  const [session, setSessionState] = useState<Session | null>(null);

  useEffect(() => {
    const stored = getSession();
    setSessionState(stored);
    setReady(true);

    if (requireAuth && !stored) {
      router.replace("/login");
    }
  }, [requireAuth, router]);

  return { ready, session };
}
