"use client"

import type {ReactNode} from "react"
import { Dialog, DialogContent, DialogTrigger } from "@/components/ui/dialog"

import './SideModal.scss'

//https://ru.stackoverflow.com/questions/1555857/%D0%9A%D0%B0%D0%BA-%D1%81%D0%B4%D0%B5%D0%BB%D0%B0%D1%82%D1%8C-%D0%B2%D1%8B%D0%B5%D0%B7%D0%B6%D0%B0%D1%8E%D1%89%D0%B5%D0%B5-%D0%BC%D0%BE%D0%B4%D0%B0%D0%BB%D1%8C%D0%BD%D0%BE%D0%B5-%D0%BE%D0%BA%D0%BD%D0%BE
interface SideModalProps {
  trigger: ReactNode
  children: ReactNode
  open?: boolean
  onOpenChange?: (open: boolean) => void
}

export function SideModal({ trigger, children, open, onOpenChange }: SideModalProps) {
  return (
    <Dialog open={open} onOpenChange={onOpenChange}>
      <DialogTrigger asChild>
        {trigger}
      </DialogTrigger>
      <DialogContent className="sm:max-w-[50vw] h-screen max-h-screen right-0 top-0 translate-x-0 translate-y-0 data-[state=open]:slide-in-from-right-full">
        {/* Контент */}
        <div className="h-full pt-12 overflow-y-auto">
          {children}
        </div>
      </DialogContent>
    </Dialog>
  )
}